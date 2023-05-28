/**
 * Copyright (c) 2017-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @noflow
 * @emails oncall+internationalization
 */

import React from "react";
import classnames from "classnames";
import Layout from "@theme/Layout";
import Link from "@docusaurus/Link";
import useDocusaurusContext from "@docusaurus/useDocusaurusContext";
import styles from "./styles.module.css";

const Description = () => (
  <section className={styles.description}>
    <div className={classnames("row", styles.row)}>
      <div className={classnames("col", styles.column)}>
        <h2>Why BigPipe?</h2>
        <div>
            BigPipe revolutionizes the way web applications load and display content. By breaking down pages into smaller components, BigPipe loads and renders essential content first, providing users with a faster initial page load. This results in a significantly improved user experience, reducing bounce rates and increasing user engagement.
        </div>
      </div>
    </div>
  </section>
);

const Index = () => {
  const { siteConfig = {} } = useDocusaurusContext();

  return (
    <Layout
      title={`${siteConfig.title} - ${siteConfig.tagline}`}
      description={siteConfig.tagline}
    >
      <header className={classnames("hero hero--primary", styles.heroBanner)}>
        <div className={classnames("container", styles.topContainer)}>
          <div>
            <h1 className="hero__title">{siteConfig.title}</h1>
            <div className={styles.sections}>
              <div>
                <p className="hero__subtitle">
                    A Microframework for Lightning-Fast Web Experiences
                </p>
                <div className={styles.buttons}>
                  <Link
                    className={classnames(
                      "button button--secondary button--lg",
                      styles.button
                    )}
                    to="https://github.com/richardDobron/bigpipe-php"
                  >
                    Try it out
                  </Link>
                  <Link
                    className={classnames(
                      "button button--info button--lg",
                      styles.button
                    )}
                    to="docs/getting_started"
                  >
                    Open documentation
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>
      <main>
        <Description />
      </main>
    </Layout>
  );
};

export default Index;
