import sys
import json
import re

import torch
import cv2
import easyocr

from PIL import Image, ImageEnhance, ImageOps
from torchvision import models, transforms
from torch import nn


# ============================================================
# SETTINGS
# ============================================================

MODEL_FILE = r"C:\Users\Admin\smartinventroy\inventory_product_model.pth"

CLASS_FILE = r"C:\Users\Admin\smartinventroy\inventory_product_classes.json"

IMAGE_SIZE = 224

CONFIDENCE_THRESHOLD = 70.0


# ============================================================
# CHECK INPUT
# ============================================================

if len(sys.argv) < 2:

    print(json.dumps({
        "error": "No image path provided."
    }))

    sys.exit(1)


image_path = sys.argv[1]


# ============================================================
# LOAD CLASS NAMES
# ============================================================

with open(
    CLASS_FILE,
    "r",
    encoding="utf-8"
) as f:

    class_names = json.load(f)


NUM_CLASSES = len(class_names)


# ============================================================
# CATEGORY FROM AI PRODUCT CLASS
# ============================================================

def category_from_product(product_class):

    # Current trained dataset is Dairy.
    # All 12 trained classes belong to Dairy.

    if product_class.startswith("Amul_"):

        return "Dairy"

    if product_class.startswith("Nandini_"):

        return "Dairy"

    return "Other"


# ============================================================
# IMAGE ENHANCEMENT
# ============================================================

def enhance_image(image):

    # Correct phone-camera orientation.
    image = ImageOps.exif_transpose(image)

    # Convert to RGB.
    image = image.convert("RGB")

    # Prevent extremely large images.
    max_size = 1400

    if max(image.size) > max_size:

        image.thumbnail(
            (max_size, max_size),
            Image.Resampling.LANCZOS
        )

    # Improve contrast.
    image = ImageEnhance.Contrast(
        image
    ).enhance(1.20)

    # Improve brightness.
    image = ImageEnhance.Brightness(
        image
    ).enhance(1.08)

    # Improve sharpness.
    image = ImageEnhance.Sharpness(
        image
    ).enhance(1.35)

    return image


# ============================================================
# OCR
# ============================================================

def run_packaging_ocr(image):

    try:

        reader = easyocr.Reader(
            ["en"],
            gpu=False,
            verbose=False
        )

        results = reader.readtext(
            image,
            detail=1,
            paragraph=False
        )

        detected_text = []

        for result in results:

            if len(result) < 3:
                continue

            text = result[1].strip()

            confidence = float(result[2])

            if (
                confidence >= 0.25
                and len(text) >= 2
            ):

                detected_text.append({
                    "text": text,
                    "confidence": round(
                        confidence * 100,
                        2
                    )
                })

        return detected_text

    except Exception as error:

        return [{
            "text": "",
            "confidence": 0,
            "error": str(error)
        }]


# ============================================================
# CLEAN OCR TEXT
# ============================================================

def clean_ocr_text(ocr_results):

    pieces = []

    for item in ocr_results:

        text = item.get(
            "text",
            ""
        ).strip()

        if not text:
            continue

        text = re.sub(
            r"\s+",
            " ",
            text
        )

        pieces.append(text)

    return pieces


# ============================================================
# LOAD CUSTOM AI MODEL
# ============================================================

def load_custom_model():

    model = models.mobilenet_v3_large(
        weights=None
    )

    input_features = (
        model.classifier[-1].in_features
    )

    model.classifier[-1] = nn.Linear(
        input_features,
        NUM_CLASSES
    )

    model.load_state_dict(
        torch.load(
            MODEL_FILE,
            map_location="cpu"
        )
    )

    model.eval()

    return model


# ============================================================
# IMAGE TRANSFORM
# ============================================================

preprocess = transforms.Compose([

    transforms.Resize(
        (IMAGE_SIZE, IMAGE_SIZE)
    ),

    transforms.ToTensor(),

    transforms.Normalize(
        mean=[
            0.485,
            0.456,
            0.406
        ],

        std=[
            0.229,
            0.224,
            0.225
        ]
    )
])


# ============================================================
# AI PRODUCT PREDICTION
# ============================================================

def predict_product(model, image):

    image_tensor = preprocess(
        image
    ).unsqueeze(0)

    with torch.no_grad():

        output = model(
            image_tensor
        )

        probabilities = torch.softmax(
            output,
            dim=1
        )[0]

    top_count = min(
        5,
        NUM_CLASSES
    )

    top_probabilities, top_indices = torch.topk(
        probabilities,
        top_count
    )

    predictions = []

    for probability, index in zip(
        top_probabilities,
        top_indices
    ):

        product = class_names[
            index.item()
        ]

        confidence = (
            probability.item() * 100
        )

        predictions.append({

            "product": product,

            "confidence": round(
                confidence,
                2
            )

        })

    return predictions


# ============================================================
# MAIN
# ============================================================

try:

    # --------------------------------------------------------
    # LOAD IMAGE
    # --------------------------------------------------------

    original_image = Image.open(
        image_path
    )


    # --------------------------------------------------------
    # ENHANCE IMAGE
    # --------------------------------------------------------

    enhanced_image = enhance_image(
        original_image
    )


    # --------------------------------------------------------
    # OCR
    # --------------------------------------------------------

    ocr_results = run_packaging_ocr(
        enhanced_image
    )

    ocr_text = clean_ocr_text(
        ocr_results
    )


    # --------------------------------------------------------
    # LOAD CUSTOM MODEL
    # --------------------------------------------------------

    model = load_custom_model()


    # --------------------------------------------------------
    # AI PRODUCT PREDICTION
    # --------------------------------------------------------

    predictions = predict_product(
        model,
        enhanced_image
    )


    # --------------------------------------------------------
    # BEST PREDICTION
    # --------------------------------------------------------

    best_prediction = predictions[0]

    predicted_product = (
        best_prediction["product"]
    )

    confidence = (
        best_prediction["confidence"]
    )


    # --------------------------------------------------------
    # AI CATEGORY
    # --------------------------------------------------------

    predicted_category = category_from_product(
        predicted_product
    )


    # --------------------------------------------------------
    # CONFIDENCE STATUS
    # --------------------------------------------------------

    if confidence >= CONFIDENCE_THRESHOLD:

        prediction_status = "CONFIDENT"

    else:

        prediction_status = "LOW_CONFIDENCE"


    # --------------------------------------------------------
    # FINAL RESULT
    # --------------------------------------------------------

    result = {

        "predictions": predictions,

        "category": predicted_category,

        "product_name": predicted_product,

        "confidence": confidence,

        "prediction_status": prediction_status,

        "ocr_text": ocr_text,

        "ocr_results": ocr_results

    }


    # --------------------------------------------------------
    # SEND JSON TO PHP
    # --------------------------------------------------------

    print(
        json.dumps(
            result,
            ensure_ascii=False
        )
    )


# ============================================================
# ERROR HANDLING
# ============================================================

except Exception as error:

    print(
        json.dumps({
            "error": str(error)
        })
    )