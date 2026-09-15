import sys
import torch
from PIL import Image
from torchvision.models import resnet50, ResNet50_Weights

if len(sys.argv) < 2:
    print("Usage: python test_product_ai.py <image_path>")
    sys.exit(1)

image_path = sys.argv[1]

# Load pretrained AI model
weights = ResNet50_Weights.DEFAULT
model = resnet50(weights=weights)
model.eval()

# Prepare image
image = Image.open(image_path).convert("RGB")
preprocess = weights.transforms()
input_tensor = preprocess(image).unsqueeze(0)

# AI prediction
with torch.no_grad():
    output = model(input_tensor)

probabilities = torch.nn.functional.softmax(output[0], dim=0)

# Get top 5 predictions
top5_prob, top5_indices = torch.topk(probabilities, 5)

categories = weights.meta["categories"]

print("\nAI PRODUCT RECOGNITION RESULTS")
print("--------------------------------")

for probability, index in zip(top5_prob, top5_indices):
    label = categories[index.item()]
    confidence = probability.item() * 100

    print(f"{label}: {confidence:.2f}%")