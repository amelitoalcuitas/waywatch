FROM node:20-alpine

WORKDIR /app

# Copy app (node_modules from local via volume mount)
COPY . .

EXPOSE 3000 24678
CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0"]
