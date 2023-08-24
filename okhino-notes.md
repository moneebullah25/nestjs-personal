# Part 2

Commands:

- Add prisma lib :

Only library with scope api are allowed to import each other

```bash
nx generate @nrwl/js:library --name=data-access-db --directory=api --compiler=swc --buildable --tags "scope:api"
```

- Add GQL :

```bash
npm i @nestjs/graphql @nestjs/mercurius graphql mercurius @nestjs/platform-fastify class-transformer class-validator
```

- Add users feature lib :

```bash
nx generate @nrwl/js:library --name=feature-user --directory=api --compiler=swc --buildable --tags "scope:api"
```

- Gen user resources :

```bash
nx g @nrwl/nest:resource --project=api-feature-user --directory=lib --type="graphql-code-first" --crud --name user
```

- Add db types lib :

```bash
nx generate @nrwl/js:library --name=generated-db-types --directory=api --compiler=swc --buildable --tags "scope:api"
```
