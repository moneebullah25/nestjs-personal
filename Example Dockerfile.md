* Think of multi stage docker file as different images. 
* In a single Dockerfile different stages can communicate with each other

The CMD instruction does not run when building the image. Instead, Docker executes it once when running the Docker container created based on our image. Therefore, using more than one CMD instruction causes the last one to override the previous CMD commands.

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