# core

My core modules that I work with things using.

Includes hacked versions of smartypants and markdown that conform to the class naming specs of my codebase. May not be up to date.

For a boilerplate MVC site, see the sample dir. Not necessary.

## Important Changes
Because things aren't super mature yet...

* Added underscores to all of model's public variables. This is to avoid confusion with database fields (these rarely start with an underscore).

## To-dos
* Re-vamp the model system so that it abstracts the schema further, models can extend each other, etc. Idea of having "table" is kind of stupid.