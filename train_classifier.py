import os
import json
import torch

from torchvision import datasets, transforms, models
from torch import nn, optim
from torch.utils.data import DataLoader, random_split

# ============================================================
# SETTINGS
# ============================================================

DATASET_DIR = r"C:\Users\Admin\smartinventroy\dataset\Dairy_Training"

MODEL_FILE = r"C:\Users\Admin\smartinventroy\inventory_product_model.pth"
CLASS_FILE = r"C:\Users\Admin\smartinventroy\inventory_product_classes.json"

IMAGE_SIZE = 224
BATCH_SIZE = 16
EPOCHS = 12
LEARNING_RATE = 0.0001

# ============================================================
# DEVICE
# ============================================================

device = torch.device(
    "cuda" if torch.cuda.is_available() else "cpu"
)

print("Using device:", device)

# ============================================================
# IMAGE TRANSFORMS
# ============================================================

train_transform = transforms.Compose([
    transforms.Resize((IMAGE_SIZE, IMAGE_SIZE)),

    transforms.RandomHorizontalFlip(p=0.5),

    transforms.RandomRotation(15),

    transforms.ColorJitter(
        brightness=0.25,
        contrast=0.25,
        saturation=0.20
    ),

    transforms.RandomAffine(
        degrees=0,
        translate=(0.08, 0.08),
        scale=(0.90, 1.10)
    ),

    transforms.ToTensor(),

    transforms.Normalize(
        mean=[0.485, 0.456, 0.406],
        std=[0.229, 0.224, 0.225]
    )
])

validation_transform = transforms.Compose([
    transforms.Resize((IMAGE_SIZE, IMAGE_SIZE)),

    transforms.ToTensor(),

    transforms.Normalize(
        mean=[0.485, 0.456, 0.406],
        std=[0.229, 0.224, 0.225]
    )
])

# ============================================================
# LOAD DATASET
# ============================================================

full_dataset = datasets.ImageFolder(
    DATASET_DIR,
    transform=train_transform
)

class_names = full_dataset.classes
num_classes = len(class_names)

print()
print("Classes found:")
for i, name in enumerate(class_names):
    print(i, "->", name)

print()
print("Total images:", len(full_dataset))
print("Number of classes:", num_classes)

# ============================================================
# TRAIN / VALIDATION SPLIT
# ============================================================

total_size = len(full_dataset)

train_size = int(total_size * 0.8)
validation_size = total_size - train_size

generator = torch.Generator().manual_seed(42)

train_dataset, validation_dataset = random_split(
    full_dataset,
    [train_size, validation_size],
    generator=generator
)

# Validation uses validation transform
validation_dataset.dataset.transform = validation_transform

train_loader = DataLoader(
    train_dataset,
    batch_size=BATCH_SIZE,
    shuffle=True,
    num_workers=0
)

validation_loader = DataLoader(
    validation_dataset,
    batch_size=BATCH_SIZE,
    shuffle=False,
    num_workers=0
)

print()
print("Training images:", train_size)
print("Validation images:", validation_size)

# ============================================================
# LOAD PRETRAINED MOBILENETV3
# ============================================================

print()
print("Loading MobileNetV3...")

weights = models.MobileNet_V3_Large_Weights.DEFAULT

model = models.mobilenet_v3_large(
    weights=weights
)

# ============================================================
# REPLACE CLASSIFIER
# ============================================================

input_features = model.classifier[-1].in_features

model.classifier[-1] = nn.Linear(
    input_features,
    num_classes
)

model = model.to(device)

# ============================================================
# LOSS + OPTIMIZER
# ============================================================

criterion = nn.CrossEntropyLoss()

optimizer = optim.Adam(
    model.parameters(),
    lr=LEARNING_RATE
)

# ============================================================
# TRAINING
# ============================================================

best_accuracy = 0.0

print()
print("==========================================")
print("STARTING TRAINING")
print("==========================================")

for epoch in range(EPOCHS):

    # -------------------------------
    # TRAIN
    # -------------------------------

    model.train()

    running_loss = 0.0
    correct = 0
    total = 0

    for images, labels in train_loader:

        images = images.to(device)
        labels = labels.to(device)

        optimizer.zero_grad()

        outputs = model(images)

        loss = criterion(
            outputs,
            labels
        )

        loss.backward()

        optimizer.step()

        running_loss += loss.item()

        _, predicted = torch.max(
            outputs,
            1
        )

        total += labels.size(0)

        correct += (
            predicted == labels
        ).sum().item()

    train_accuracy = (
        100 * correct / total
    )

    # -------------------------------
    # VALIDATION
    # -------------------------------

    model.eval()

    validation_correct = 0
    validation_total = 0

    with torch.no_grad():

        for images, labels in validation_loader:

            images = images.to(device)
            labels = labels.to(device)

            outputs = model(images)

            _, predicted = torch.max(
                outputs,
                1
            )

            validation_total += labels.size(0)

            validation_correct += (
                predicted == labels
            ).sum().item()

    validation_accuracy = (
        100 * validation_correct /
        validation_total
    )

    print(
        f"Epoch [{epoch + 1}/{EPOCHS}] "
        f"Loss: {running_loss / len(train_loader):.4f} "
        f"Train: {train_accuracy:.2f}% "
        f"Validation: {validation_accuracy:.2f}%"
    )

    # Save best model
    if validation_accuracy > best_accuracy:

        best_accuracy = validation_accuracy

        torch.save(
            model.state_dict(),
            MODEL_FILE
        )

        print(
            "  ✓ Best model saved!"
        )

# ============================================================
# SAVE CLASS NAMES
# ============================================================

with open(
    CLASS_FILE,
    "w",
    encoding="utf-8"
) as f:

    json.dump(
        class_names,
        f,
        indent=4
    )

# ============================================================
# COMPLETE
# ============================================================

print()
print("==========================================")
print("TRAINING COMPLETE")
print("==========================================")

print(
    f"Best validation accuracy: "
    f"{best_accuracy:.2f}%"
)

print()
print("Model:")
print(MODEL_FILE)

print()
print("Classes:")
print(CLASS_FILE)