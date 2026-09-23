import os
import random
import torch

from PIL import Image, ImageEnhance, ImageFilter
import torchvision.transforms as transforms

# ============================================================
# SETTINGS
# ============================================================

SOURCE_DIR = r"C:\Users\Admin\smartinventroy\dataset\Dairy"
OUTPUT_DIR = r"C:\Users\Admin\smartinventroy\dataset\Dairy_Augmented"

AUGMENTATIONS_PER_IMAGE = 3

os.makedirs(OUTPUT_DIR, exist_ok=True)

# Image extensions we accept
VALID_EXTENSIONS = (".jpg", ".jpeg", ".png", ".webp")


# ============================================================
# AUGMENTATION FUNCTIONS
# ============================================================

def rotate_image(image):
    angle = random.uniform(-25, 25)
    return image.rotate(
        angle,
        expand=True,
        fillcolor=(255, 255, 255)
    )


def change_brightness(image):
    factor = random.uniform(0.55, 1.45)
    return ImageEnhance.Brightness(image).enhance(factor)


def change_contrast(image):
    factor = random.uniform(0.65, 1.4)
    return ImageEnhance.Contrast(image).enhance(factor)


def blur_image(image):
    radius = random.uniform(0.5, 2.0)
    return image.filter(ImageFilter.GaussianBlur(radius))


def sharpen_image(image):
    factor = random.uniform(1.2, 2.0)
    return ImageEnhance.Sharpness(image).enhance(factor)


def crop_image(image):
    width, height = image.size

    crop_ratio = random.uniform(0.75, 0.95)

    new_width = int(width * crop_ratio)
    new_height = int(height * crop_ratio)

    if new_width <= 0 or new_height <= 0:
        return image

    left = random.randint(0, max(0, width - new_width))
    top = random.randint(0, max(0, height - new_height))

    return image.crop(
        (left, top, left + new_width, top + new_height)
    )


def add_noise(image):
    transform = transforms.Compose([
        transforms.ToTensor(),
        transforms.Lambda(
            lambda x: torch.clamp(
                x + torch.randn_like(x) * 0.025,
                0,
                1
            )
        ),
        transforms.ToPILImage()
    ])

    return transform(image)


# ============================================================
# REALISTIC MESSY AUGMENTATION
# ============================================================

def make_messy_image(image):

    image = image.convert("RGB")

    # Randomly apply several realistic problems
    operations = [
        rotate_image,
        change_brightness,
        change_contrast,
        blur_image,
        sharpen_image,
        crop_image
    ]

    # Pick 2–4 different effects
    number_of_effects = random.randint(2, 4)

    selected_operations = random.sample(
        operations,
        number_of_effects
    )

    for operation in selected_operations:
        image = operation(image)

    return image


# ============================================================
# PROCESS DATASET
# ============================================================

image_count = 0
generated_count = 0

print("==========================================")
print("MESSY IMAGE AUGMENTATION")
print("==========================================")
print()
print("Source:", SOURCE_DIR)
print("Output:", OUTPUT_DIR)
print()


if not os.path.exists(SOURCE_DIR):
    print("ERROR: Source folder does not exist.")
    print("Create/prepare:")
    print(SOURCE_DIR)
    exit()


for root, dirs, files in os.walk(SOURCE_DIR):

    for filename in files:

        if not filename.lower().endswith(VALID_EXTENSIONS):
            continue

        image_path = os.path.join(root, filename)

        try:
            image = Image.open(image_path)
            image.load()

        except Exception:
            print("Skipping damaged image:", filename)
            continue

        image_count += 1

        base_name = os.path.splitext(filename)[0]

        for i in range(AUGMENTATIONS_PER_IMAGE):

            try:
                augmented = make_messy_image(image)

                # Resize to manageable size
                augmented.thumbnail((1200, 1200))

                output_name = (
                    f"{base_name}_messy_{i + 1}.jpg"
                )

                output_path = os.path.join(
                    OUTPUT_DIR,
                    output_name
                )

                augmented.save(
                    output_path,
                    "JPEG",
                    quality=90
                )

                generated_count += 1

            except Exception as e:
                print(
                    "Could not augment:",
                    filename,
                    "|",
                    e
                )


print()
print("==========================================")
print("AUGMENTATION COMPLETE")
print("==========================================")
print("Original images:", image_count)
print("Messy images generated:", generated_count)
print()
print("Saved to:")
print(OUTPUT_DIR)