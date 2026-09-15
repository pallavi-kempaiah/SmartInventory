import easyocr
import re

reader = easyocr.Reader(['en'])

image_path = "test-receipt.png"

results = reader.readtext(image_path)

print("\n--- DETECTED TEXT ---\n")

for result in results:
    text = result[1]
    confidence = result[2]

    print(f"{text} | Confidence: {confidence:.2f}")


print("\n--- POSSIBLE PRODUCT LINES ---\n")

for result in results:
    text = result[1]
    confidence = result[2]

    # Ignore very low-confidence text
    if confidence < 0.40:
        continue

    # Look for lines containing quantity + price
    if re.search(r'\b\d+\s*[xX]\s*\d', text):
        print("Possible item:", text)

    # Look for common product-like text
    elif any(char.isalpha() for char in text) and any(char.isdigit() for char in text):
        print("Possible product:", text)