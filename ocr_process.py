import sys
import easyocr
import re
import json

if len(sys.argv) < 2:
    print(json.dumps({"error": "No image path provided."}))
    sys.exit(1)

image_path = sys.argv[1]

reader = easyocr.Reader(['en'], gpu=False, verbose=False)

results = reader.readtext(image_path)

items = []

for result in results:
    box = result[0]
    text = result[1].strip()
    confidence = result[2]

    if confidence < 0.30:
        continue

    x = sum(point[0] for point in box) / 4
    y = sum(point[1] for point in box) / 4

    items.append({
        "text": text,
        "confidence": confidence,
        "x": x,
        "y": y
    })


# ---------------------------------------
# Currency detection
# ---------------------------------------

price_pattern = re.compile(
    r'^\s*[$₹]\s*\d+(?:[.,]\d{1,2})?\s*$'
)


# ---------------------------------------
# Ignore obvious non-product text
# ---------------------------------------

ignored_words = [
    "corner store",
    "street",
    "date",
    "tax",
    "subtotal",
    "total",
    "visa",
    "cash",
    "change",
    "thank you",
    "shopping",
    "****"
]


# ---------------------------------------
# Find price detections
# ---------------------------------------

price_items = []

for item in items:

    text = item["text"].strip()

    if price_pattern.match(text):

        price_text = (
            text.replace("$", "")
                .replace("₹", "")
                .replace(",", "")
                .strip()
        )

        try:
            price = float(price_text)

            price_items.append({
                "price": price,
                "x": item["x"],
                "y": item["y"]
            })

        except ValueError:
            pass


# ---------------------------------------
# Find product-like text
# ---------------------------------------

text_items = []

for item in items:

    text = item["text"].strip()
    lower = text.lower()

    # Skip currency
    if price_pattern.match(text):
        continue

    # Skip obvious unwanted text
    if any(word in lower for word in ignored_words):
        continue

    # Skip dates
    if re.search(r'\d{4}[-/]\d{1,2}[-/]\d{1,2}', text):
        continue

    # Skip times
    if re.search(r'\b\d{1,2}:\d{2}\b', text):
        continue

    # Need at least some letters
    if len(re.sub(r'[^A-Za-z]', '', text)) < 3:
        continue

    text_items.append(item)


# ---------------------------------------
# Match each product with nearest price
# ---------------------------------------

products = []

for item in text_items:

    closest_price = None
    closest_distance = float("inf")

    for price_item in price_items:

        # Price should normally be to the right
        if price_item["x"] <= item["x"]:
            continue

        # Calculate distance
        x_distance = price_item["x"] - item["x"]
        y_distance = abs(price_item["y"] - item["y"])

        # Ignore prices on completely different receipt sections
        if y_distance > 80:
            continue

        distance = x_distance + (y_distance * 2)

        if distance < closest_distance:
            closest_distance = distance
            closest_price = price_item


    if closest_price is None:
        continue


    # Avoid pairing a product with a very distant price
    if closest_price["x"] - item["x"] > 500:
        continue


    products.append({
        "product": item["text"],
        "quantity": 1,
        "price": closest_price["price"]
    })


# ---------------------------------------
# Remove duplicates
# ---------------------------------------

clean_products = []

for product in products:

    duplicate = False

    for existing in clean_products:

        if product["product"].lower() == existing["product"].lower():

            duplicate = True
            break

    if not duplicate:

        clean_products.append(product)


# ---------------------------------------
# Output
# ---------------------------------------

print(json.dumps(clean_products, ensure_ascii=False))