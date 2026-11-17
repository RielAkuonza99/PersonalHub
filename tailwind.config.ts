import type { Config } from "tailwindcss";

const config: Config = {
  content: ["./app/**/*.{ts,tsx}", "./components/**/*.{ts,tsx}", "./lib/**/*.ts"],
  theme: {
    extend: {
      colors: {
        ink: "#08080d",
        panel: "#12131b",
        panel2: "#171925",
        volt: "#6c2bff",
        lime: "#c8ff1f",
        flame: "#ff5a2c",
        sky: "#2f7bff",
      },
      fontFamily: {
        display: ["var(--font-display)", "Permanent Marker", "Comic Sans MS", "cursive"],
        hand: ["var(--font-hand)", "Gochi Hand", "Comic Sans MS", "cursive"],
        sans: ["var(--font-body)", "Poppins", "system-ui", "sans-serif"],
      },
    },
  },
  plugins: [],
};
export default config;
