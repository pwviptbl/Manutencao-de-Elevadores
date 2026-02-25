FROM node:22-alpine

WORKDIR /app

# Dependências do sistema
RUN apk add --no-cache git

# Instalar dependências (cache layer)
COPY package*.json ./
RUN npm ci 2>/dev/null || true

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
