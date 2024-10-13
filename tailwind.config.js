/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  variants: {
    extend: {
      backgroundColor: ['hover'],
      textColor: ['hover'],
    },
  },
}

