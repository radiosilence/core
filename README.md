# core

Copyright 2010 James Cleveland. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY James Cleveland "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMES CLEVELAND OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of James Cleveland.

My core modules that I work with things using. Features python-style code [PEP 8](http://www.python.org/dev/peps/pep-0008/), pythonic imports, actually using namespaces, modularity, MVC microframework for fast development, 3rd party ease of use, update script for use with cron or whatever.

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
* Use interfaces for models.
* 