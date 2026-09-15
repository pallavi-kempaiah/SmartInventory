import sys
import json
import torch
from PIL import Image
from torchvision.models import resnet50, ResNet50_Weights

if len(sys.argv) < 2:
    print(json.dumps({"error": "No image path provided."}))
    sys.exit(1)

image_path = sys.argv[1]

try:
    # Load pretrained AI model
    weights = ResNet50_Weights.DEFAULT
    model = resnet50(weights=weights)
    model.eval()

    # Load and prepare image
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

    predictions = []

    for probability, index in zip(top5_prob, top5_indices):
        predictions.append({
            "product": categories[index.item()],
            "confidence": round(probability.item() * 100, 2)
        })

    print(json.dumps(predictions))

except Exception as e:
    print(json.dumps({
        "error": str(e)
    }))