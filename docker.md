In Docker, we have a shared kernel that is used to run every application hence faster performance. So basically instead of virtualizing hardware like virtual machine does we virtualize operating system.

There are 3 main components in the Docker world:
1. Docker File: Contains the instruction how to build the image
2. Image: It is the snapshot of our application containing all the dependencies and the operating system. Image is immutable and can be used to start or spinup multiple containers. 
3. Container: Actual software running in a real world

Creating a Docker File
1. First we specify the image which is pulled down from the cloud. We can also upload our own image through variety of different docker registries.
2. Now to install dependencies we could do any like `RUN npm i` or `RUN apt-get sl`. This will install the dependencies into our image. We could also setup environment variables using `ENV port=8080` etc.
3. Now last step to run using `CMD ["echo", "Docker is easy"]`

```Docker
FROM ubuntu:20.04
RUN apt-get sl
ENV port=8080
CMD ["echo", "Docker is easy"]
```

And now we can create the image file using command:
```bash
docker build -t myapp ./
```
It will go through each step in our Docker file and build image layer by layer. Then we can run the image using command:
```bash
docker run myapp
```

Now same above application `myapp` if requires more resources, we can run it on multiple machines, multiple clouds, On-Prem (OnPrem containers are stateless and can be scaled to run as many instances as needed). 

---

**Docker and Kubernetes Essentials for Deploying NestJS Applications**

**Introduction to Docker:**

- Docker provides containerization technology, allowing applications to run consistently across different environments by packaging them along with their dependencies.
- Containers share the host OS kernel, leading to efficient resource utilization and quick application startup.

**Core Docker Components:**

1. **Docker File:**
   - A script-like text file containing instructions to build a Docker image.
   - Instructions include setting up the base image, copying files, installing dependencies, configuring environment variables, and more.

2. **Image:**
   - A snapshot of an application along with its dependencies and runtime environment.
   - Immutable and reproducible; used to create containers.
   - Can be pulled from container registries like Docker Hub or private repositories.

3. **Container:**
   - An instance of an image, running as a separate process on the host OS.
   - Isolated from other containers and the host system.
   - Lightweight, portable, and easily scalable.

**Creating a Dockerfile:**

1. Choose a Base Image:
   - Start with a base image, often based on a specific OS or runtime (e.g., Ubuntu, Node.js).
   
2. Define Instructions:
   - Use instructions like `RUN`, `COPY`, `ENV`, `WORKDIR` to set up the image environment.
   - Install dependencies, copy application code, configure environment variables, and more.

3. Example Dockerfile:

```Docker
FROM node:14
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
EXPOSE 3000
CMD ["npm", "start"]
```

**Building and Running Docker Images:**

1. **Building Images:**
   - Use `docker build -t <tag-name> <path>` to build an image from a Dockerfile.
   - Docker follows the instructions in the Dockerfile to create a layered image.
   
2. **Running Containers:**
   - Use `docker run <image-name>` to start a container from an image.
   - Containers are isolated instances with their own file systems and network interfaces.

**Introduction to Kubernetes:**

- Kubernetes is an open-source container orchestration platform that automates the deployment, scaling, and management of containerized applications.

**Core Kubernetes Concepts:**

1. **Pods:**
   - The smallest deployable units in Kubernetes.
   - Can contain one or more containers that share the same network and storage resources.
   
2. **Deployments:**
   - Used to manage the lifecycle of application instances (Pods).
   - Ensure a specified number of replicas (Pods) are running and handle updates and rollbacks.
   
3. **Services:**
   - Expose Pods to network traffic within or outside the cluster.
   - Types include ClusterIP, NodePort, and LoadBalancer.
   
4. **ConfigMaps and Secrets:**
   - Store configuration data and sensitive information separately from application code.
   - Can be injected into Pods as environment variables or mounted as files.

**Deploying NestJS Applications with Kubernetes:**

1. **Container Registry:**
   - Push your Docker image to a container registry.
   
2. **Kubernetes Deployment:**
   - Define a Deployment manifest specifying image, replicas, and configurations.
   
3. **Applying Manifests:**
   - Use `kubectl apply -f <manifest-file>` to deploy resources.
   
4. **Scaling and Updates:**
   - Scale your application by adjusting the replica count.
   - Update your application by changing the image version in the Deployment manifest.

**Conclusion:**

Mastering Docker and Kubernetes essentials empowers you to deploy NestJS applications efficiently and reliably. Docker's containerization simplifies packaging, while Kubernetes provides robust orchestration for scaling and management. Continuously practice and explore advanced topics for further expertise.

---