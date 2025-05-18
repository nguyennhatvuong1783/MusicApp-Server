from flask import Flask, request, jsonify
from dotenv import load_dotenv
import os, faiss
from typing import List
from sentence_transformers import SentenceTransformer
import pickle
import google.generativeai as genai

load_dotenv()
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")

genai.configure(api_key=GEMINI_API_KEY)

app = Flask(__name__)
model = SentenceTransformer('all-MiniLM-L6-v2')  # Miễn phí và nhanh
index = faiss.read_index("songs.index")  # File đã lưu vector
with open("songs.pkl", "rb") as f:
    songs = pickle.load(f)  # Chứa dữ liệu gốc

@app.route("/suggest", methods=["POST"])
def suggest():
    query = request.json["text"]
    vec = model.encode([query])
    D, I = index.search(vec, 5)
    top_songs = [songs[i] for i in I[0]]

    prompt = "Dưới đây là danh sách bài hát:\n" + "\n".join(
        [f"{s['title']}" for s in top_songs]
    )
    prompt += f"\nNgười dùng nói: {query}\nGợi ý 3 bài hát phù hợp, chỉ từ danh sách trên."

    model_gemini = genai.GenerativeModel('gemini-2.0-flash')
    response = model_gemini.generate_content(prompt)
    return jsonify({"result": response.text})

if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=5000)