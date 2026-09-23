from icrawler.builtin import BingImageCrawler
import os
import shutil

# ============================================================
# SETTINGS
# ============================================================

BASE_DIR = r"C:\Users\Admin\smartinventroy\dataset\Dairy_Nandini"

# ============================================================
# START COMPLETELY CLEAN
# ============================================================

if os.path.exists(BASE_DIR):
    print("Removing old Nandini dataset...")
    shutil.rmtree(BASE_DIR)

os.makedirs(BASE_DIR, exist_ok=True)

# ============================================================
# NANDINI SEARCHES
# ============================================================

queries = {
    "milk": [
        "Nandini milk packet Karnataka",
        "Nandini toned milk packet",
        "Nandini double toned milk packet",
        "Nandini Good Life milk",
    ],

    "curd": [
        "Nandini curd packet Karnataka",
        "Nandini curd pouch",
        "Nandini thick curd",
        "Nandini set curd",
    ],

    "butter": [
        "Nandini butter packet",
        "Nandini salted butter",
        "Nandini butter box",
    ],

    "cheese": [
        "Nandini cheese packet",
        "Nandini processed cheese",
        "Nandini cheese slices",
    ],

    "paneer": [
        "Nandini paneer packet",
        "Nandini fresh paneer",
        "Nandini paneer pack",
    ],

    "ghee": [
        "Nandini ghee",
        "Nandini ghee jar",
        "Nandini pure ghee",
        "Nandini ghee container",
    ]
}

# ============================================================
# DOWNLOAD
# ============================================================

for product_type, search_queries in queries.items():

    folder = os.path.join(
        BASE_DIR,
        product_type
    )

    os.makedirs(
        folder,
        exist_ok=True
    )

    for query in search_queries:

        print()
        print("--------------------------------")
        print("Downloading:", query)
        print("--------------------------------")

        crawler = BingImageCrawler(
            storage={
                "root_dir": folder
            },
            downloader_threads=4
        )

        crawler.crawl(
            keyword=query,
            max_num=5
        )

print()
print("==========================================")
print("NANDINI DATASET DOWNLOAD COMPLETE")
print("==========================================")
print()
print("Saved to:")
print(BASE_DIR)