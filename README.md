# core

My core modules that I work with things using. Features python-style coding, pythonic imports, actually using namespaces, modularity, MVC microframework for fast development, 3rd party ease of use, update script for use with cron or whatever.

Includes 3rd party modules.


For a boilerplate MVC site, see the sample dir. Not necessary.

All you really need to do to use it is this:

    include('wherever/core/core.php');

Then you can start importing modules, like:

    import('core.session.php');

    import('core.router');
    $router = new \Core\Router();

    import('code.db.*');

It seems kind of dumb to import using period and then use the namespacing backslash, but backslash escapes in strings and apparently this escaped the wonderous minds of the PHP team.

Included are 3rd party modules like reCAPTCHA and Markdown. They are easy to import:
 
  import('3rdparty.markdown');

And they are accessible on the global namespace.

## Important Changes
Because things aren't super mature yet...

* LOADS of things have changed, old code probably won't work, nobody uses it anyway so I don't care.

## To-dos
* Re-vamp the model system so that it abstracts the schema further, models can extend each other, etc. Idea of having "table" is kind of stupid.