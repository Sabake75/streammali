import { ImageResponse } from "next/og";

export const alt = "StreamMali, le cinéma malien, en un clic";
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

export default function Image() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          justifyContent: "center",
          padding: "80px",
          background: "#faf6f0",
          color: "#241b10",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 24 }}>
          <div
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              width: 110,
              height: 110,
              borderRadius: 28,
              background: "linear-gradient(135deg, #a66414, #ba1239)",
              color: "white",
              fontSize: 56,
            }}
          >
            ▶
          </div>
          <div style={{ display: "flex", fontSize: 76, fontWeight: 700 }}>
            Stream<span style={{ color: "#a66414" }}>Mali</span>
          </div>
        </div>
        <div style={{ display: "flex", marginTop: 40, fontSize: 40, maxWidth: 900 }}>
          Le cinéma malien, en un clic
        </div>
        <div style={{ display: "flex", marginTop: 16, fontSize: 28, color: "#6b5a44" }}>
          Films, clips et web-séries de créateurs maliens, 100 FCFA la vidéo
        </div>
      </div>
    ),
    { ...size },
  );
}
