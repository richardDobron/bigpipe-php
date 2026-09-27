import React from "react";
import clsx from "clsx";
import Layout from "@theme/Layout";
import Link from "@docusaurus/Link";
import CodeBlock from "@theme/CodeBlock";
import Tabs from "@theme/Tabs";
import TabItem from "@theme/TabItem";
import useDocusaurusContext from "@docusaurus/useDocusaurusContext";
import styles from "./styles.module.css";

const examples = [
  {
    id: "link",
    label: "Links",
    client: {
      language: "html",
      title: "HTML",
      code: `<ul class="cart">
  <li id="item-123">
    Headphones
    <a href="#"
       ajaxify="/ajax/remove.php?id=123"
       rel="async">Remove</a>
  </li>
</ul>`,
    },
    server: `<?php
// /ajax/remove.php
$response = new \\dobron\\BigPipe\\AsyncResponse();

$response->remove('#item-' . $_GET['id']);
$response->appendContent(
    'ul.cart',
    '<li>Item removed.</li>'
);

$response->send();`,
    note: (
      <>
        A link with <code>rel="async"</code> requests its <code>ajaxify</code> URL. The server answers with DOM
        operations and BigPipe applies them. No page reload, no frontend code to write.
      </>
    ),
  },
  {
    id: "form",
    label: "Forms",
    client: {
      language: "html",
      title: "HTML",
      code: `<form action="/ajax/subscribe.php"
      method="POST"
      rel="async">
  <input name="email" type="email">
  <button type="submit">Subscribe</button>
</form>
<p class="status"></p>`,
    },
    server: `<?php
// /ajax/subscribe.php
$response = new \\dobron\\BigPipe\\AsyncResponse();

$response->setContent(
    'p.status',
    'Thanks, check your inbox!'
);
$response->redirect('/welcome', 2000);

$response->send();`,
    note: (
      <>
        Forms with <code>rel="async"</code> are submitted in the background. The response can update the page,
        reload it or redirect after a delay.
      </>
    ),
  },
  {
    id: "dialog",
    label: "Dialogs",
    client: {
      language: "html",
      title: "HTML",
      code: `<a href="#"
   ajaxify="/ajax/dialog.php"
   rel="dialog">Open dialog</a>`,
    },
    server: `<?php
// /ajax/dialog.php
$response = new \\dobron\\BigPipe\\DialogResponse();

$response->setTitle('Dialog title')
    ->setBody('html <strong>content</strong>')
    ->setFooter('<button>Close</button>')
    ->dialog();

$response->send();`,
    note: (
      <>
        <code>rel="dialog"</code> opens a dialog rendered on the server. Dialogs are Bootstrap-compatible and can
        be stacked on top of each other.
      </>
    ),
  },
  {
    id: "module",
    label: "JavaScript modules",
    client: {
      language: "javascript",
      title: "UserLoggedInAlert.js",
      code: `export default function UserLoggedInAlert(username) {
  alert(\`Welcome, \${username}!\`);
}`,
    },
    server: `<?php
$response = new \\dobron\\BigPipe\\AsyncResponse();

$response->bigPipe()->require(
    "require('UserLoggedInAlert')",
    ['Marvin']
);

$response->send();`,
    note: (
      <>
        Call any JavaScript module straight from PHP and pass it arguments. With{" "}
        <Link to="/docs/transport_markers">transport markers</Link> they can even be elements, maps or sets.
      </>
    ),
  },
];

const features = [
  {
    icon: "⚡",
    title: "Ajax without JavaScript",
    description: (
      <>
        Add <code>rel="async"</code> to links and forms and they load in the background. The server decides what
        happens on the page.
      </>
    ),
  },
  {
    icon: "🪄",
    title: "DOM operations from PHP",
    description: (
      <>
        Set, append, prepend, replace or remove content by a CSS selector with the{" "}
        <Link to="/docs/domops">DOMOPS API</Link>, from the backend or the frontend.
      </>
    ),
  },
  {
    icon: "🧩",
    title: "Call JavaScript modules",
    description: (
      <>
        Require your own modules from PHP and pass them arguments. Keep the logic on the server and the behaviour
        in small, reusable modules.
      </>
    ),
  },
  {
    icon: "🖥",
    title: "Dialogs",
    description: (
      <>
        Render <Link to="/docs/dialogs">dialogs</Link> on the server, open them with <code>rel="dialog"</code> and
        attach controllers to react to their events.
      </>
    ),
  },
  {
    icon: "📡",
    title: "Event system",
    description: (
      <>
        <Link to="/docs/arbiter">Arbiter</Link> lets modules talk to each other with a simple publish/subscribe
        API.
      </>
    ),
  },
  {
    icon: "🏢",
    title: "Inspired by Facebook",
    description: (
      <>
        Based on{" "}
        <a href="https://engineering.fb.com/2010/06/04/web/bigpipe-pipelining-web-pages-for-high-performance/">
          BigPipe
        </a>
        , the technique Facebook built to make its pages load faster. Works with plain PHP,{" "}
        <Link to="/docs/laravel_integration">Laravel</Link> and <Link to="/docs/react_integration">React</Link>.
      </>
    ),
  },
];

const installCommands = `composer require richarddobron/bigpipe
npm install bigpipe-util`;

// The layers of the logo (static/img/bigpipe-icon.svg), split to animate each of them.
const logoLayers = [
  "M.749 21.381a1.543 1.543 0 0 1 0-2.646L31.605.221a1.53 1.53 0 0 1 1.588 0L64.05 18.735a1.543 1.543 0 0 1 0 2.646L33.193 39.895a1.54 1.54 0 0 1-1.588 0Z",
  "M62.462 31.078 32.399 49.116 2.337 31.078a1.543 1.543 0 0 0-1.588 2.646l30.856 18.513a1.54 1.54 0 0 0 1.588 0L64.05 33.724a1.543 1.543 0 0 0-1.588-2.646Z",
  "M62.462 43.421 32.399 61.458 2.337 43.421a1.543 1.543 0 0 0-1.588 2.645L31.605 64.58a1.54 1.54 0 0 0 1.588 0L64.05 46.066a1.543 1.543 0 0 0-1.588-2.645Z",
];

function HeroLogo() {
  return (
    <div className={styles.heroLogo} aria-hidden="true">
      <div className={styles.heroGlow} />
      <svg className={styles.heroLogoSvg} viewBox="-4 -10 72.8 84.8">
        {logoLayers.map((d, i) => (
          <g key={i} className={styles.layerDrop} style={{ "--layer": i }}>
            <path className={styles.layer} d={d} />
          </g>
        ))}
      </svg>
    </div>
  );
}

function Hero() {
  const { siteConfig } = useDocusaurusContext();

  return (
    <header className={styles.hero}>
      <div className={clsx("container", styles.heroContainer)}>
        <div className={styles.heroText}>
          <span className={styles.badge}>Microframework for PHP &amp; JavaScript</span>
          <h1 className={styles.heroTitle}>{siteConfig.title}</h1>
          <p className={styles.heroTagline}>
            Update the page, open dialogs and call JavaScript modules right from PHP, so that your app feels fast
            without building a single-page app.
          </p>
          <div className={styles.buttons}>
            <Link className="button button--primary button--lg" to="/docs/getting_started">
              Get started
            </Link>
            <Link className="button button--outline button--secondary button--lg" to="http://bigpipe.xf.cz">
              Live demo
            </Link>
          </div>
          <div className={styles.install}>
            <CodeBlock language="bash">{installCommands}</CodeBlock>
          </div>
        </div>
        <HeroLogo />
      </div>
    </header>
  );
}

function InAction() {
  return (
    <section className={styles.section}>
      <div className="container">
        <h2 className={styles.sectionTitle}>BigPipe in action</h2>
        <p className={styles.sectionLead}>
          Mark up what should be ajaxified, answer with an <code>AsyncResponse</code> and BigPipe does the rest in
          the browser.
        </p>
        <Tabs className={styles.exampleTabs}>
          {examples.map(({ id, label, client, server, note }) => (
            <TabItem key={id} value={id} label={label}>
              <div className="row">
                <div className="col col--6">
                  <CodeBlock language={client.language} title={client.title}>
                    {client.code}
                  </CodeBlock>
                  <p className={styles.exampleNote}>{note}</p>
                </div>
                <div className="col col--6">
                  <CodeBlock language="php" title="PHP">
                    {server}
                  </CodeBlock>
                </div>
              </div>
            </TabItem>
          ))}
        </Tabs>
        <p className={styles.exampleFootnote}>
          See all of them working in the <a href="http://bigpipe.xf.cz">demo app</a>.
        </p>
      </div>
    </section>
  );
}

function Features() {
  return (
    <section className={clsx(styles.section, styles.sectionAlt)}>
      <div className="container">
        <h2 className={styles.sectionTitle}>Why BigPipe?</h2>
        <p className={styles.sectionLead}>
          BigPipe loads and renders the essential content first and updates only the parts of the page that change.
          Pages feel faster and users stay engaged.
        </p>
        <div className={styles.featureGrid}>
          {features.map(({ icon, title, description }) => (
            <div key={title} className={styles.featureCard}>
              <div className={styles.featureIcon} aria-hidden="true">
                {icon}
              </div>
              <h3>{title}</h3>
              <p>{description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function GetStarted() {
  return (
    <section className={styles.section}>
      <div className={clsx("container", styles.narrow)}>
        <h2 className={styles.sectionTitle}>Get started in minutes</h2>
        <p className={styles.sectionLead}>Install both packages, then wire them up in your entrypoint and layout.</p>
        <CodeBlock language="bash" title="Terminal">
          {installCommands}
        </CodeBlock>
        <CodeBlock language="javascript" title="resources/js/app.js">{`import Primer from 'bigpipe-util/dist/Primer';

Primer();

window.require = (modulePath) => {
  return modulePath.startsWith('bigpipe-util/')
    ? require('bigpipe-util/dist/' + modulePath.replace(/^bigpipe-util\\/(src|dist)\\//, '') + '.js').default
    : require('./' + modulePath).default;
};`}</CodeBlock>
        <CodeBlock language="php" title="Page footer">{`<script>
    (new (require("bigpipe-util/dist/ServerJS"))).handle(<?=json_encode(\\dobron\\BigPipe\\BigPipe::jsmods())?>);
</script>`}</CodeBlock>
        <p className={styles.center}>
          <Link to="/docs/getting_started">Read the full guide →</Link>
        </p>
      </div>
    </section>
  );
}

export default function Home() {
  const { siteConfig } = useDocusaurusContext();

  return (
    <Layout title={siteConfig.title} description={siteConfig.tagline}>
      <Hero />
      <main>
        <InAction />
        <Features />
        <GetStarted />
      </main>
    </Layout>
  );
}
