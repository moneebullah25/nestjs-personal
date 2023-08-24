# NX Monorepo

## Introduction

Imagine you have a big box of different types of LEGO blocks. Each type of block can do a different thing, like wheels for cars, windows for houses, and so on. Now, think of a "monorepo" as a super organized box where you keep all your LEGO blocks, sorted by what they do.

"Nx" is like a special tool that helps you keep track of all these blocks in your organized box. It helps you build things faster by telling you how to put the blocks together to make cool stuff like cars, houses, and even whole LEGO cities!

So, when people talk about "Nx monorepo," they mean using the Nx tool to manage a big collection of code pieces (like the LEGO blocks) that work together to make websites, apps, or other computer programs. Just like using special tools to build amazing LEGO creations, using Nx helps developers build awesome software projects by keeping everything neat and helping them work together smoothly.

### Package-Based Repo:

Think of this like having a separate box for each puzzle. Each puzzle has its own box with its pieces and instructions inside. If you want to work on a specific puzzle, you grab that box and focus only on that puzzle. In a package-based repo for software, each part of a big project is like a puzzle. They have their own "box" (folder) with code and instructions inside. This makes it clear and simple to work on one thing at a time.

### Integrated Repo:

Now imagine you have a big table with lots of sections, and you're working on all the puzzles at the same time on that table. Each puzzle's pieces are mixed up with the others, and you need to be careful not to mix them up or lose any. In an integrated repo for software, all the pieces of a big project are mixed together in the same "table" (repository). This means you can work on different parts all at once, but you need to be extra organized to keep everything from getting tangled.

### Difference between Package-Based vs Integrated Repository

So, the main difference is that in a package-based repo, each part of the project is neatly separated, like separate puzzle boxes, while in an integrated repo, all the parts are mixed together, like working on all the puzzles at the same time on a big table. Both ways have their pros and cons, and which one to use depends on the project and the team's preferences!

## Library Types

### Feature libraries:

Developers should consider feature libraries as libraries that implement smart UI (with access to data sources) for specific business use cases or pages in an application.

### UI libraries:

A UI library contains only presentational components (also called "dumb" components).

### Data-access libraries:

A data-access library contains code for interacting with a back-end system. It also includes all the code related to state management. For Frontend application a library with access to API's or some kind of storage. For Backend application a library with access to database or some storage like S3 bucket etcc.

### Utility libraries:

A utility library contains low-level utilities used by many libraries and applications.