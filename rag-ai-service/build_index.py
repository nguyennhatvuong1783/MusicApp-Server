import psycopg2, faiss, pickle
from sentence_transformers import SentenceTransformer

conn = psycopg2.connect("host=localhost dbname=music_db user=postgres password=12345678")
cur = conn.cursor()
cur.execute("SELECT id, title FROM songs")
rows = cur.fetchall()

model = SentenceTransformer('all-MiniLM-L6-v2')
docs = [f"{title}" for (_, title) in rows]
vectors = model.encode(docs)

index = faiss.IndexFlatL2(vectors.shape[1])
index.add(vectors)
faiss.write_index(index, "songs.index")

songs = [{"id": r[0], "title": r[1]} for r in rows]
with open("songs.pkl", "wb") as f:
    pickle.dump(songs, f)
