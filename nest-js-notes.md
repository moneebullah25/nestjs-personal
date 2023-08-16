# Overview
## First Steps
### Lint and Prettier
NestJS comes preinstalled with lint and prettier just need to run the following command in the terminal
```bash
# Lint and autofix with eslint
$ npm run lint

# Format with prettier
$ npm run format
```
---
### SWC Speedy Web Compiler
SWC (Speedy Web Compiler) is an extensible Rust-based platform that can be used for both compilation and bundling. Using SWC with Nest CLI is a great and simple way to significantly speed up your development process.
SWC is approximately x20 times faster than the default TypeScript compiler.
```json
"compilerOptions": {
    "builder": {
        "type": "swc", 
        "options": {
        "swcrcPath": "infrastructure/.swcrc"
        }
    },
    "typeCheck": true
}
```
By default the typeCheck is set to false so it is a good pratice to set it to true
***Installation***
To get started, first install a few packages:
```bash
$ npm i --save-dev @swc/cli @swc/core
```
***Getting started***
Once the installation process is complete, you can use the swc builder with Nest CLI, as follows:
```bash
$ nest start -b swc
# OR nest start --builder swc
```
---
### Controllers
Controllers are responsible for handling incoming requests and returning responses to the client.
![image](https://docs.nestjs.com/assets/Controllers_1.png)
---
### Status code
As mentioned, the response status code is always 200 by default, except for POST requests which are 201. We can easily change this behavior by adding the @HttpCode(...) decorator at a handler level.
```typescript
import { HttpCode } from "@nestjs/common";
@Post()
@HttpCode(204)
create() {
  return 'This action adds a new cat';
}
```
---
### DI fundamentals
Dependency injection is an inversion of control (IoC) technique wherein you delegate instantiation of dependencies to the IoC container (in our case, the NestJS runtime system), instead of doing it in your own code imperatively. Let's examine what's happening in this example from the Providers chapter.

First, we define a provider. The @Injectable() decorator marks the CatsService class as a provider.

cats.service.ts
```typescript
import { Injectable } from '@nestjs/common';
import { Cat } from './interfaces/cat.interface';

@Injectable()
export class CatsService {
  private readonly cats: Cat[] = [];

  findAll(): Cat[] {
    return this.cats;
  }
}
```
Then we request that Nest inject the provider into our controller class:

cats.controller.tsJS
```typescript
import { Controller, Get } from '@nestjs/common';
import { CatsService } from './cats.service';
import { Cat } from './interfaces/cat.interface';

@Controller('cats')
export class CatsController {
  constructor(private catsService: CatsService) {}

  @Get()
  async findAll(): Promise<Cat[]> {
    return this.catsService.findAll();
  }
}
```
Finally, we register the provider with the Nest IoC container:

app.module.tsJS
```typescript
import { Module } from '@nestjs/common';
import { CatsController } from './cats/cats.controller';
import { CatsService } from './cats/cats.service';

@Module({
  controllers: [CatsController],
  providers: [CatsService],
})
export class AppModule {}
```
What exactly is happening under the covers to make this work? There are three key steps in the process:
- In cats.service.ts, the @Injectable() decorator declares the CatsService class as a class that can be managed by the Nest IoC container.
- In cats.controller.ts, CatsController declares a dependency on the CatsService token with constructor injection:
```typescript
  constructor(private catsService: CatsService)
```
- In app.module.ts, we associate the token CatsService with the class CatsService from the cats.service.ts file. We'll see below exactly how this association (also called registration) occurs.
---









# Websockets
### Gateways
Most of the concepts discussed elsewhere in this documentation, such as dependency injection, decorators, exception filters, pipes, guards and interceptors, apply equally to gateways.

![Image](https://docs.nestjs.com/assets/Gateways_1.png)

In Nest, a gateway is simply a class annotated with @WebSocketGateway() decorator. Technically, gateways are platform-agnostic which makes them compatible with any WebSockets library once an adapter is created.

Gateways can be treated as providers; this means they can inject dependencies through the class constructor. Also, gateways can be injected by other classes (providers and controllers) as well.

#### Installation
To start building WebSockets-based applications, first install the required package:
```bash
$ npm i --save @nestjs/websockets @nestjs/platform-socket.io
```


# Fundamentals Course
# Testing

```bash
npm run test # for unit test
npm run test:cov # for converage test
npm run test:e2e # for e2e test
```

## Unit Testing

For each controller or service file, there exists a .spec file for unit testing the component

## e2e Testing

For end-to-end testing we put the test directory in side of src directory.
For e2e testing file extension **_e2e-spec.ts_**. The end-to-end system tests the higher level features such as user interaction with the application

## .spec.ts

```typescript
import { Test, TestingModule } from '@nestjs/testing';
import { CoffeesService } from '../services/coffees.service';

type MockRepository<T = any> = Partial<Record<keyof Repository<T>, jest.Mock>>;
const createMockRepository = <T = any>(): MockRepository<T> => ({
  findOne: jest.fn(),
  create: jest.fn(),
});

// test filename can be .spec or .test
describe('CoffeesService', () => {
  let service: CoffeesService;

  // function passed into the beforeEach hook is executed before every test [SETUP PHASE]
  beforeEach(async () => {
    // The Test class is userful for providing the an application ExecutionContext that mocks the Nest runtime and gives hooks to tweak with the Mock Nest runtime
    // Test.createTestingModule takes the same object we pass to the @Module Decorator to manage dependencies same time we do here.
    const module: TestingModule = await Test.createTestingModule({
      providers: [
        // BAD Practice, We could provide the required providers in the providers array which are used by CoffeesService. Hence the isolation unit test best practices would be violated
        CoffeesService,
        //   constructor(
        //     @InjectRepository(Coffee)
        //     private readonly coffeeRepository: Repository<Coffee>,

        //     @InjectRepository(Flavor)
        //     private readonly flavorRepository: Repository<Flavor>,

        //     private readonly connection: Connection,
        // ) {}
        // Since in the constructor of the CoffeesService we use the following dependencies from typeorm we define them there to mock there values
        // Mocking Values
        { provide: Connection, useValue: {} },
        {
          provide: getRepositoryToken(Flavor),
          useValue: createMockRepository(),
        },
        {
          provide: getRepositoryToken(Coffee),
          useValue: createMockRepository(),
        },
      ],
    }).compile();
    // Compile is similar to bootstraping the mock Nest runtime by compiling the root module and link the entire application and call listen on each endpoint.
    // Compile method returns us the TestingModule instance which gives us access to the

    // By default this will provide the Default scope for provider which is Singleton based class instance
    coffeeRepository = module.get<MockRepository>(getRepositoryToken(Coffee));
    service = module.get<CoffeesService>(CoffeesService);
    // @Injectable({ scope: Scope.REQUEST }) We can change the default before like below
    // service = await module.resolve<CoffeesService>(CoffeesService);
  });

  // Other helper functions are beforeAll, afterEach, afterAll

  it('should be defined', () => {
    expect(service).toBeDefined();
  });

  // Seperate name for each of the service to determine which test is this
  describe('findOne', () => {
    // success case
    describe('when coffee with ID exists', () => {
      it('should return the coffee object', async () => {
        const coffeeId = '1';
        const extectedCoffee = {};

        coffeeRepository.findOne.mockReturnValue(extectedCoffee);

        // If we run without the above line we will get error stating that `this.coffeeRepository.findOne is not a function`
        const coffee = await service.findOne(coffeeId);
        expect(coffee).toEqual(extectedCoffee);
      });
    });
    // fail case
    describe('otherwise', () => {
      it('should throw the "NotFoundException"', async () => {
        const coffeeId = '1';

        coffeeRepository.findOne.mockReturnValue(undefined); // We mock the return value for findOne to be undefined

        try {
          await service.findOne(coffeeId);
        } catch (err) {
          expect(err).toBeInstanceOf(NotFoundException);
          expect(err.message).toEqual(`Coffee #${coffeeId} not found`);
        }
      });
    });
  });
});
```

## CLI Commands

test:watch run unit test in watch mode and run test if file change.

```bash
npm run test:watch -- user.service
```

## Scopes in NestJS Application

A provider can have any of the following scopes:

- DEFAULT A single instance of the provider is shared across the entire application. The instance lifetime is tied directly to the application lifecycle. Once the application has bootstrapped, all singleton providers have been instantiated. Singleton scope is used by default.
- REQUEST A new instance of the provider is created exclusively for each incoming request. The instance is garbage-collected after the request has completed processing.
- TRANSIENT Transient providers are not shared across consumers. Each consumer that injects a transient provider will receive a new, dedicated instance.


# GraphQL

## Mapped Types

### Partial 

```typescript
@InputType()
class CreateUserInput {
  @Field()
  email: string;

  @Field()
  password: string;

  @Field()
  firstName: string;
}
```

By default, all of these fields are required. To create a type with the same fields, but with each one optional, use PartialType() passing the class reference (CreateUserInput) as an argument:

```typescript
import { PartialType }  from "@nestjs/graphql";
@InputType()
export class UpdateUserInput extends PartialType(CreateUserInput) {}
```

The PartialType() function returns a type (class) with all the properties of the input type set to optional 

The PartialType() function takes an optional second argument that is a reference to a decorator factory. This argument can be used to change the decorator function applied to the resulting (child) class. If not specified, the child class effectively uses the same decorator as the parent class (the class referenced in the first argument). In the example above, we are extending CreateUserInput which is annotated with the @InputType() decorator. Since we want UpdateUserInput to also be treated as if it were decorated with @InputType(), we didn't need to pass InputType as the second argument. If the parent and child types are different, (e.g., the parent is decorated with @ObjectType), we would pass InputType as the second argument. For example:

In short, if parent component is initialized with ObjectType rather than InputType we can convert the behavious of the dto class to input type by passing the second argument as Input type when declaring the child UpdateUserInput component.

```typescript
@InputType()
export class UpdateUserInput extends PartialType(User, InputType) {}
```

### Pick

The PickType() function constructs a new type (class) by picking a set of properties from an input type. For example, suppose we start with a type like:

```typescript
@InputType()
class CreateUserInput {
  @Field()
  email: string;

  @Field()
  password: string;

  @Field()
  firstName: string;
}
```

We can pick a set of properties from this class using the PickType() utility function:

```typescript
@InputType()
export class UpdateEmailInput extends PickType(CreateUserInput, [
  'email',
] as const) {}
```

### Omit

The OmitType() function constructs a type by picking all properties from an input type and then removing a particular set of keys. For example, suppose we start with a type like:

```typescript
@InputType()
class CreateUserInput {
  @Field()
  email: string;

  @Field()
  password: string;

  @Field()
  firstName: string;
}
```

We can generate a derived type that has every property exceptemail as shown below. In this construct, the second argument to OmitType is an array of property names.

```typescript
@InputType()
export class UpdateUserInput extends OmitType(CreateUserInput, [
  'email',
] as const) {}
```

### Intersection

The IntersectionType() function combines two types into one new type (class). For example, suppose we start with two types like:

```typescript
@InputType()
class CreateUserInput {
  @Field()
  email: string;

  @Field()
  password: string;
}

@ObjectType()
export class AdditionalUserInfo {
  @Field()
  firstName: string;

  @Field()
  lastName: string;
}
```

We can generate a new type that combines all properties in both types.

```typescript
@InputType()
export class UpdateUserInput extends IntersectionType(
  CreateUserInput,
  AdditionalUserInfo,
) {}
```

### Composition
The type mapping utility functions are composable. For example, the following will produce a type (class) that has all of the properties of the CreateUserInput type except for email, and those properties will be set to optional:

```typescript
@InputType()
export class UpdateUserInput extends PartialType(
  OmitType(CreateUserInput, ['email'] as const),
) {}
```

## Resolvers 

### Object types

Most of the definitions in a GraphQL schema are object types. Each object type you define should represent a domain object that an application client might need to interact with. For example, our sample API needs to be able to fetch a list of authors and their posts, so we should define the Author type and Post type to support this functionality.

```typescript
// authors/models/author.model.ts

import { Field, Int, ObjectType } from '@nestjs/graphql';
import { Post } from './post';

@ObjectType()
export class Author {
  @Field(type => Int)
  id: number;

  @Field({ nullable: true })
  firstName?: string;

  @Field({ nullable: true })
  lastName?: string;

  @Field(type => [Post])
  posts: Post[];
}
```

The above Author object type definition will cause Nest to generate the following SDL:

```typescript
type Author {
  id: Int!
  firstName: String
  lastName: String
  posts: [Post!]!
}
```

The options object can have any of the following key/value pairs:

- nullable: for specifying whether a field is nullable (in SDL, each field is non-nullable by default); boolean
- description: for setting a field description; string
- deprecationReason: for marking a field as deprecated; string
For example:

```typescript
@Field({ description: `Book title`, deprecationReason: 'Not useful in v2 schema' })
title: string;
```

- HINT
---
You can also add a description to, or deprecate, the whole object type: @ObjectType({ description: 'Author model' }).

---

When the field is an array, we must manually indicate the array type in the Field() decorator's type function, as shown below:

```typescript
@Field(type => [Post])
posts: Post[];
```
- HINT
---
Using array bracket notation ([ ]), we can indicate the depth of the array. For example, using [[Int]] would represent an integer matrix.

---

To declare that an array's items (not the array itself) are nullable, set the nullable property to 'items' as shown below:

```typescript
@Field(type => [Post], { nullable: 'items' })
posts: Post[];
```

- HINT
---
If both the array and its items are nullable, set nullable to 'itemsAndList' instead.

---

Now that the Author object type is created, let's define the Post object type.

```typescript
// posts/models/post.model.ts

import { Field, Int, ObjectType } from '@nestjs/graphql';

@ObjectType()
export class Post {
  @Field(type => Int)
  id: number;

  @Field()
  title: string;

  @Field(type => Int, { nullable: true })
  votes?: number;
}
```

The Post object type will result in generating the following part of the GraphQL schema in SDL:

```typescript
type Post {
  id: Int!
  title: String!
  votes: Int
}
```


