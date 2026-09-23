import sys
import json
import torch

from PIL import Image
from torchvision import transforms, models
from torch import nn


# ============================================================
# SETTINGS
# ============================================================

MODEL_FILE = r"C:\Users\Admin\smartinventroy\inventory_product_model.pth"

CLASS_FILE = r"C:\Users\Admin\smartinventroy\inventory_product_classes.json"

IMAGE_SIZE = 224


# ============================================================
# CHECK IMAGE ARGUMENT
# ============================================================

if len(sys.argv) < 2:
    print("Usage:")
    print("python predict_category.py path_to_image.jpg")
    sys.exit(1)

IMAGE_PATH = sys.argv[1]


# ============================================================
# DEVICE
# ============================================================

device = torch.device(
    "cuda" if torch.cuda.is_available() else "cpu"
)

print("Using device:", device)


# ============================================================
# LOAD CLASSES
# ============================================================

with open(
    CLASS_FILE,
    "r",
    encoding="utf-8"
) as f:

    class_names = json.load(f)

num_classes = len(class_names)


# ============================================================
# LOAD MODEL
# ============================================================

model = models.mobilenet_v3_large(
    weights=None
)

input_features = model.classifier[-1].in_features

model.classifier[-1] = nn.Linear(
    input_features,
    num_classes
)

model.load_state_dict(
    torch.load(
        MODEL_FILE,
        map_location=device
    )
)

model = model.to(device)

model.eval()


# ============================================================
# IMAGE PREPROCESSING
# ============================================================

transform = transforms.Compose([
    transforms.Resize((IMAGE_SIZE, IMAGE_SIZE)),

    transforms.ToTensor(),

    transforms.Normalize(
        mean=[0.485, 0.456, 0.406],
        std=[0.229, 0.224, 0.225]
    )
])


# ============================================================
# LOAD IMAGE
# ============================================================

try:

    image = Image.open(IMAGE_PATH).convert("RGB")

except Exception as e:

    print("Could not open image:")
    print(e)
    sys.exit(1)


image_tensor = transform(image)

image_tensor = image_tensor.unsqueeze(0)

image_tensor = image_tensor.to(device)


# ============================================================
# PREDICTION
# ============================================================

with torch.no_grad():

    output = model(image_tensor)

    probabilities = torch.softmax(
        output,
        dim=1
    )

    confidence, predicted_index = torch.max(
        probabilities,
        dim=1
    )


predicted_class = class_names[
    predicted_index.item()
]

confidence_percent = (
    confidence.item() * 100
)


# ============================================================
# TOP 5 PREDICTIONS
# ============================================================

top_probabilities, top_indices = torch.topk(
    probabilities,
    min(5, num_classes)
)


print()
print("==========================================")
print("AI PRODUCT PREDICTION")
print("==========================================")

print()
print("Product:")
print(predicted_class)

print()
print(
    f"Confidence: {confidence_percent:.2f}%"
)

print()
print("Top predictions:")

for probability, index in zip(
    top_probabilities[0],
    top_indices[0]
):

    print(
        f"{class_names[index.item()]:20} "
        f"{probability.item() * 100:.2f}%"
    )

print()
print("==========================================")