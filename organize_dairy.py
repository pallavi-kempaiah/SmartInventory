import os
import shutil

BASE_DIR = r"C:\Users\Admin\smartinventroy\dataset"

CLEAN_DIR = os.path.join(BASE_DIR, "Dairy")
AUGMENTED_DIR = os.path.join(BASE_DIR, "Dairy_Augmented")

TRAINING_DIR = os.path.join(BASE_DIR, "Dairy_Training")

VALID_EXTENSIONS = (".jpg", ".jpeg", ".png", ".webp")

# ============================================================
# CREATE THE 12 CLASSES
# ============================================================

CLASSES = [
    "Amul_Milk",
    "Nandini_Milk",
    "Amul_Curd",
    "Nandini_Curd",
    "Amul_Butter",
    "Nandini_Butter",
    "Amul_Cheese",
    "Nandini_Cheese",
    "Amul_Paneer",
    "Nandini_Paneer",
    "Amul_Ghee",
    "Nandini_Ghee"
]

# Start fresh
if os.path.exists(TRAINING_DIR):
    print("Removing old Dairy_Training folder...")
    shutil.rmtree(TRAINING_DIR)

for class_name in CLASSES:
    os.makedirs(
        os.path.join(TRAINING_DIR, class_name),
        exist_ok=True
    )


# ============================================================
# DETERMINE CLASS FROM FILENAME
# ============================================================

def get_class_from_filename(filename):

    name = filename.lower()

    brand = None
    product = None

    if "amul" in name:
        brand = "Amul"

    elif "nandini" in name:
        brand = "Nandini"

    if "milk" in name:
        product = "Milk"

    elif "curd" in name:
        product = "Curd"

    elif "butter" in name:
        product = "Butter"

    elif "cheese" in name:
        product = "Cheese"

    elif "paneer" in name:
        product = "Paneer"

    elif "ghee" in name:
        product = "Ghee"

    if brand and product:
        return f"{brand}_{product}"

    return None


# ============================================================
# COPY CLEAN IMAGES
# ============================================================

clean_count = 0
augmented_count = 0
skipped_count = 0


def process_folder(source_dir, is_augmented=False):

    global clean_count
    global augmented_count
    global skipped_count

    if not os.path.exists(source_dir):
        print("Folder not found:", source_dir)
        return

    for filename in os.listdir(source_dir):

        if not filename.lower().endswith(VALID_EXTENSIONS):
            continue

        class_name = get_class_from_filename(filename)

        if class_name is None:
            skipped_count += 1
            print("SKIPPED:", filename)
            continue

        source_path = os.path.join(
            source_dir,
            filename
        )

        destination_folder = os.path.join(
            TRAINING_DIR,
            class_name
        )

        destination_path = os.path.join(
            destination_folder,
            filename
        )

        try:

            shutil.copy2(
                source_path,
                destination_path
            )

            if is_augmented:
                augmented_count += 1
            else:
                clean_count += 1

        except Exception as e:

            skipped_count += 1

            print(
                "ERROR:",
                filename,
                e
            )


# ============================================================
# PROCESS BOTH DATASETS
# ============================================================

print()
print("==========================================")
print("ORGANIZING DAIRY DATASET")
print("==========================================")
print()

print("Processing clean images...")
process_folder(CLEAN_DIR)

print("Processing messy images...")
process_folder(
    AUGMENTED_DIR,
    is_augmented=True
)


# ============================================================
# SHOW FINAL COUNTS
# ============================================================

print()
print("==========================================")
print("DAIRY DATASET ORGANIZATION COMPLETE")
print("==========================================")
print()

print("Clean images copied:", clean_count)
print("Messy images copied:", augmented_count)
print("Skipped:", skipped_count)

print()
print("Classes:")
print()

for class_name in CLASSES:

    folder = os.path.join(
        TRAINING_DIR,
        class_name
    )

    count = len([
        f for f in os.listdir(folder)
        if f.lower().endswith(VALID_EXTENSIONS)
    ])

    print(f"{class_name:20} : {count}")

print()
print("Training dataset:")
print(TRAINING_DIR)