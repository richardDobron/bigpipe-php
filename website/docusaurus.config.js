/**
 * Copyright (c) 2017-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @noflow
 * @emails oncall+internationalization
 */

module.exports = {
  title: "BigPipe",
  tagline: "A Microframework for Lightning-Fast Web Experiences.",
  url: "https://richarddobron.github.io",
  baseUrl: "/bigpipe-php/",
  favicon: "img/bigpipe.svg",
  projectName: "bigpipe-php",
  organizationName: "richardDobron",
  scripts: ["https://buttons.github.io/buttons.js"],
  themeConfig: {
    navbar: {
      logo: {
        alt: "BigPipe Logo",
        src: "img/bigpipe.svg"
      },
      items: [
        { to: "docs/getting_started", label: "Docs", position: "right" },
        {
          href: "https://github.com/richardDobron/bigpipe-php",
          label: "GitHub",
          position: "right"
        }
      ]
    },
    footer: {
      style: "dark",
        logo: {
            alt: "Richard's Blog",
            src: "/img/blog.svg",
            href: "https://dobron.showwcase.com/"
        },
      copyright: `Copyright © ${new Date().getFullYear()} Richard Dobroň`,
      links: [
        {
          title: "Docs",
          items: [
            { label: "Getting Started", to: "docs/getting_started" },
            {
              label: "How it works",
              to: "docs/how_it_works"
            }
          ]
        },
        {
          title: "BigPipe",
          items: [
            {
              label: "JavaScript",
              href: "https://github.com/richardDobron/bigpipe-util"
            },
            {
              label: "PHP",
              href: "https://github.com/richardDobron/bigpipe-php"
            }
          ]
        },
      ]
    },
    image: "img/bigpipe.svg",
    algolia: {
      apiKey: "9a5a805d18c37abc7339b217ec941de4",
      indexName: "fbt",
    },
  },
  presets: [
    [
      "@docusaurus/preset-classic",
      {
        docs: {
          path: "../docs",
          sidebarPath: require.resolve("./sidebars.js"),
          showLastUpdateAuthor: true,
          showLastUpdateTime: true
        },
        theme: {
          customCss: require.resolve("./src/css/custom.css")
        }
      }
    ]
  ]
};
