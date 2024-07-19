/** @type {import('tailwindcss').Config} */
export default {
    content: [
      "./index.html",
      "./src/**/*.{vue,js,ts,jsx,tsx,html}",
    ],
    theme: {
      extend: {
        colors: {
          'regal-purple':'#38213F',
          'purple':'#6B21A8',
          'nice-purple':'#9F89C2'
        },
      },
    },
    plugins: [],
}
