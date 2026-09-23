from icrawler.builtin import BingImageCrawler
import os
import shutil

BASE_DIR = r"C:\Users\Admin\smartinventroy\dataset\Dairy_Amul"

# Start completely fresh
if os.path.exists(BASE_DIR):
    print("Removing old Amul dataset...")
    shutil.rmtree(BASE_DIR)

os.makedirs(BASE_DIR, exist_ok=True)

queries = {
    "milk": [
        "Amul milk packet",
        "Amul Taaza milk packet",
        "Amul Gold milk packet",
        "Amul Slim n Trim milk"
    ],

    "curd": [
        "Amul curd packet",
        "Amul Masti dahi",
        "Amul curd cup",
        "Amul yogurt"
    ],

    "butter": [
        "Amul butter packet",
        "Amul butter 500g",
        "Amul salted butter"
    ],

    "cheese": [
        "Amul cheese packet",
        "Amul cheese slices",
        "Amul processed cheese"
    ],

    "paneer": [
        "Amul paneer packet",
        "Amul fresh paneer",
        "Amul paneer pack"
    ],

    "ghee": [
        "Amul ghee",
        "Amul ghee jar",
        "Amul pure ghee"
    ]
}

for product_type, search_queries in queries.items():

    folder = os.path.join(BASE_DIR, product_type)
    os.makedirs(folder, exist_ok=True)

    for query in search_queries:

        print("\nDownloading:", query)

        crawler = BingImageCrawler(
            storage={"root_dir": folder},
            downloader_threads=4
        )

        crawler.crawl(
            keyword=query,
            max_num=5
        )

print("\n================================")
print("AMUL DATASET DOWNLOAD COMPLETE")
print("================================")
print(BASE_DIR)