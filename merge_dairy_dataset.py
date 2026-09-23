import os
import shutil

BASE_DIR = r"C:\Users\Admin\smartinventroy\dataset"

AMUL_DIR = os.path.join(BASE_DIR, "Dairy_Amul")
NANDINI_DIR = os.path.join(BASE_DIR, "Dairy_Nandini")
OUTPUT_DIR = os.path.join(BASE_DIR, "Dairy")

VALID_EXTENSIONS = (".jpg", ".jpeg", ".png", ".webp")

# Remove old merged folder if it exists
if os.path.exists(OUTPUT_DIR):
    print("Removing old Dairy folder...")
    shutil.rmtree(OUTPUT_DIR)

os.makedirs(OUTPUT_DIR, exist_ok=True)

total_copied = 0
total_skipped = 0


def copy_images(source_dir, brand_name):

    global total_copied, total_skipped

    if not os.path.exists(source_dir):
        print(f"WARNING: {source_dir} does not exist.")
        return

    print()
    print("Processing:", brand_name)

    for product_type in os.listdir(source_dir):

        product_dir = os.path.join(source_dir, product_type)

        if not os.path.isdir(product_dir):
            continue

        # Keep product type in filename
        # Example: Amul_milk_001.jpg
        files = os.listdir(product_dir)

        image_number = 1

        for filename in files:

            if not filename.lower().endswith(VALID_EXTENSIONS):
                continue

            source_path = os.path.join(product_dir, filename)

            new_filename = (
                f"{brand_name}_{product_type}_{image_number}.jpg"
            )

            destination_path = os.path.join(
                OUTPUT_DIR,
                new_filename
            )

            try:
                shutil.copy2(
                    source_path,
                    destination_path
                )

                total_copied += 1
                image_number += 1

            except Exception as e:
                print("Could not copy:", filename)
                print("Reason:", e)
                total_skipped += 1


# Merge both brands
copy_images(AMUL_DIR, "Amul")
copy_images(NANDINI_DIR, "Nandini")


print()
print("==========================================")
print("DAIRY DATASET MERGE COMPLETE")
print("==========================================")
print("Images copied:", total_copied)
print("Images skipped:", total_skipped)
print()
print("Final dataset:")
print(OUTPUT_DIR)