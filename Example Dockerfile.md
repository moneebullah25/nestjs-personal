* Think of multi stage docker file as different images. 
* In a single Dockerfile different stages can communicate with each other

Build Stage

```Dockerfile
FROM node:18-alpine AS build

WORKDIR /usr/src/app

COPY package*.json ./

RUN npm install 

COPY . . 

RUN npm run build
```

Production Stage

```Dockerfile
FROM node:18-alpine

WORKDIR /usr/src/app

COPY --from=build /usr/src/app/dist ./dist

COPY package*.json ./

RUN npm install --only=production

RUN rm package*.json

EXPOSE 3000

CMD [ "node", "dist/main.js" ]
```