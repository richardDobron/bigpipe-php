<img src="resources/images/logo.svg">

## Demo

Try the app with [live demo](http://bigpipe.etexweb.sk).

A Laravel 13 application with Vite. It has two parts:

- **Tutorials** (`/tutorial/...`, listed on `/`): a working example of every feature, with its code and a panel that
  shows what the server sent back for each request:
  - pagelets: streaming, parallel rendering, fallbacks, phases and refresh; lazy pagelets; a poller the server stops;
    morph, which keeps the focus of a form re-rendered on the server; the Bootloader; events (Arbiter);
  - DOM operations, dialogs, forms, payload, transport markers, custom configuration, DOM references, redirecting and
    expired CSRF tokens.
- **The demo app** (`/app/...`): the [Laravel recipes](https://bigpipe.etexweb.sk/docs/laravel_recipes)
  as a working application: an infinite feed, forms with validation errors, a confirmation dialog, a cart, a dashboard
  of streamed pagelets, live notifications, avatar upload with progress, unsaved changes warning and expired sessions.
  There is no login: every visitor gets a demo user and a playground of their own (see below).

The documentation (`/docs`) is the markdown of the repository (`docs/`), rendered by `App\Docs\Docs` (CommonMark, with the
front matter, anchors, table of contents and titled code blocks of the Docusaurus site). It reads `docs/` of the demo,
or else the one of the repository next to it; the deploy copies `docs/` into the demo. The pages and their order are in
`config/docs.php`.

Every page renders into one layout (`resources/views/layouts/base.blade.php`), so every link is a page transition:
`Controller::page()` and `Controller::streamedPage()` answer one with the `canvas` section of the page.

## Installation

PHP 8.3 or higher, Node.js and Composer.

```bash
git clone https://github.com/richardDobron/bigpipe-php.git
cd bigpipe-php/demo-app

composer install        # uses richarddobron/bigpipe from the parent directory
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link

npm run build           # or npm run dev
php artisan serve
```

`bigpipe-util` 2.x is installed from npm. Until it is released, build it from its repository (`npm run build` in
`bigpipe-util`) and use it with `npm install ../../bigpipe-util` (the path of your checkout) instead.

The pagelets of the dashboard wait for slow "APIs" at the same time. With `php artisan serve` run
`PHP_CLI_SERVER_WORKERS=4 php artisan serve`: with one worker, the stylesheet and the script of a page are served only
after its streamed response, and the browser shows the whole page at once.

The server calls the JavaScript modules in `resources/js` by their path, e.g. `tutorial/Image` for
`resources/js/tutorial/Image.js`. `app.js` finds them with `import.meta.glob`: after adding a module while `npm run dev`
is running, save `app.js` (or restart Vite), or the module is "not found by the module loader".

`npm run build` makes `app.js` a single classic script (see `vite.config.js`), loaded in the `<head>` before the inline
scripts of BigPipe, so the browser shows every pagelet as soon as it arrives. The dev server (`npm run dev`) serves
modules only: there BigPipe sends module scripts too (`BigPipe::setScriptType('module')`), which run once the page is
parsed, so the streamed pagelets are shown together at the end of the page. To watch them stream, build the assets.

Tailwind is compiled by Vite from the classes of the views (`resources/css/app.css`).

## Tests

```bash
php artisan test
```

## Playgrounds

Every visitor is a tenant. The first request to `/app/...` creates a demo user with a `tenant_id` and the sample data
(`App\Tenancy\Playground`); posts, comments, products, cart lines and alerts carry the `tenant_id`, and the
`BelongsToTenant` trait limits their queries to the tenant of the signed-in user and sets it on new records.

A playground is removed an hour after it was created by `php artisan demo:prune`, scheduled hourly. The server needs the
Laravel scheduler for it:

```bash
* * * * * cd /path/to/demo-app && php artisan schedule:run >> /dev/null 2>&1
```
