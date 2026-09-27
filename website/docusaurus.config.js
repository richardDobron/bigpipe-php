// @ts-check

const { themes } = require("prism-react-renderer");

const repoUrl = "https://github.com/richardDobron/bigpipe-php";

/** @type {import('@docusaurus/types').Config} */
module.exports = {
  title: "BigPipe",
  tagline: "A microframework for lightning-fast web experiences.",
  url: "https://richarddobron.github.io",
  baseUrl: "/bigpipe-php/",
  favicon: "img/bigpipe-icon.svg",
  organizationName: "richardDobron",
  projectName: "bigpipe-php",
  trailingSlash: false,
  onBrokenLinks: "throw",
  markdown: {
    format: "detect",
    hooks: {
      onBrokenMarkdownLinks: "throw",
    },
  },
  presets: [
    [
      "classic",
      /** @type {import('@docusaurus/preset-classic').Options} */
      {
        docs: {
          path: "../docs",
          sidebarPath: require.resolve("./sidebars.js"),
          showLastUpdateAuthor: true,
          showLastUpdateTime: true,
          editUrl: `${repoUrl}/edit/main/docs/`,
        },
        blog: false,
        theme: {
          customCss: [
            require.resolve("@fontsource-variable/inter/index.css"),
            require.resolve("@fontsource-variable/fira-code/index.css"),
            require.resolve("./src/css/custom.css"),
          ],
        },
      },
    ],
  ],
  themes: [
    [
      require.resolve("@easyops-cn/docusaurus-search-local"),
      {
        hashed: true,
        indexBlog: false,
        docsRouteBasePath: "docs",
        docsDir: "../docs",
      },
    ],
  ],
  themeConfig: {
    image: "img/bigpipe.svg",
    colorMode: {
      respectPrefersColorScheme: true,
    },
    navbar: {
      logo: {
        alt: "BigPipe Logo",
        src: "img/bigpipe.svg",
      },
      items: [
        { type: "doc", docId: "getting_started", label: "Docs", position: "left" },
        { type: "doc", docId: "how_it_works", label: "How it works", position: "left" },
        { href: "https://richarddobron.github.io/bigpipe-util/", label: "JavaScript API", position: "left" },
        { href: "http://bigpipe.xf.cz", label: "Demo", position: "right" },
        { href: "https://packagist.org/packages/richarddobron/bigpipe", label: "Packagist", position: "right" },
        { href: "https://www.npmjs.com/package/bigpipe-util", label: "npm", position: "right" },
        { href: repoUrl, label: "GitHub", position: "right" },
      ],
    },
    footer: {
      style: "dark",
      logo: {
        alt: "Richard's Blog",
        src: "img/blog.svg",
        href: "https://dobron.showwcase.com/",
      },
      links: [
        {
          title: "Docs",
          items: [
            { label: "Getting started", to: "docs/getting_started" },
            { label: "How it works", to: "docs/how_it_works" },
            { label: "DOMOPS API", to: "docs/domops" },
            { label: "JavaScript API", href: "https://richarddobron.github.io/bigpipe-util/" },
          ],
        },
        {
          title: "Community",
          items: [
            { label: "Issues", href: `${repoUrl}/issues` },
            { label: "Changelog", href: `${repoUrl}/blob/main/CHANGELOG.md` },
            { label: "Demo app", href: "http://bigpipe.xf.cz" },
          ],
        },
        {
          title: "BigPipe",
          items: [
            { label: "PHP (bigpipe-php)", href: repoUrl },
            { label: "JavaScript (bigpipe-util)", href: "https://github.com/richardDobron/bigpipe-util" },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} Richard Dobroň`,
    },
    prism: {
      theme: themes.github,
      darkTheme: themes.dracula,
      additionalLanguages: ["php", "bash", "json"],
    },
  },
};
