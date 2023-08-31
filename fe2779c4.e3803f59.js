(window.webpackJsonp=window.webpackJsonp||[]).push([[14],{75:function(e,t,n){"use strict";n.r(t),n.d(t,"frontMatter",(function(){return c})),n.d(t,"metadata",(function(){return p})),n.d(t,"rightToc",(function(){return i})),n.d(t,"default",(function(){return s}));var r=n(1),a=n(6),o=(n(0),n(79)),c={id:"domops",title:"\ud83e\ude84 DOMOPS API",sidebar_label:"DOMOPS"},p={unversionedId:"domops",id:"domops",isDocsHomePage:!1,title:"\ud83e\ude84 DOMOPS API",description:"The DOMOPS API (Document Object Model Operations) provides a set of powerful methods that allow you to manipulate the behavior and content of the frontend of your application. These methods can be utilized both in the backend and directly within the frontend code.",source:"@site/..\\docs\\domops.md",slug:"/domops",permalink:"/bigpipe-php/docs/domops",version:"current",lastUpdatedBy:"Richard Dobro\u0148",lastUpdatedAt:1685257762,sidebar_label:"DOMOPS",sidebar:"docs",previous:{title:"How it works",permalink:"/bigpipe-php/docs/how_it_works"},next:{title:"\ud83e\uddf0 Transport Markers API",permalink:"/bigpipe-php/docs/transport_markers"}},i=[{value:"Methods",id:"methods",children:[{value:"<strong>setContent</strong>",id:"setcontent",children:[]},{value:"<strong>appendContent</strong>",id:"appendcontent",children:[]},{value:"<strong>prependContent</strong>",id:"prependcontent",children:[]},{value:"<strong>insertAfter</strong>",id:"insertafter",children:[]},{value:"<strong>insertBefore</strong>",id:"insertbefore",children:[]},{value:"<strong>remove</strong>",id:"remove",children:[]},{value:"<strong>replace</strong>",id:"replace",children:[]},{value:"<strong>eval</strong>",id:"eval",children:[]}]},{value:"Live Example",id:"live-example",children:[]}],l={rightToc:i};function s(e){var t=e.components,n=Object(a.a)(e,["components"]);return Object(o.b)("wrapper",Object(r.a)({},l,n,{components:t,mdxType:"MDXLayout"}),Object(o.b)("p",null,"The DOMOPS API (Document Object Model Operations) provides a set of powerful methods that allow you to manipulate the behavior and content of the frontend of your application. These methods can be utilized both in the backend and directly within the frontend code."),Object(o.b)("p",null,"These methods offer a robust toolkit for dynamic manipulation of the DOM elements and content, enhancing the interactive experience of your application's frontend."),Object(o.b)("h2",{id:"methods"},"Methods"),Object(o.b)("h3",{id:"setcontent"},Object(o.b)("strong",{parentName:"h3"},"setContent")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"This method sets the content of a specified element."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"<?php\n$response = new \\dobron\\BigPipe\\DialogResponse();\n\n$response->setContent(\n    'span.current-date', // selector\n    date('Y-m-d H:i:s')\n);\n\n$response->send();\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"import DOM from \"bigpipe-util/src/core/DOM\";\n\nDOM.prependContent(\n    document.querySelector('span.current-date'), // element\n    new Date().toLocaleString()\n);\n")))),Object(o.b)("h3",{id:"appendcontent"},Object(o.b)("strong",{parentName:"h3"},"appendContent")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"Use this method to insert content as the last child of a specified element."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->appendContent(\n    'ul.logs',\n    '<li>appended line</li>'\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.appendContent(\n    document.querySelector('ul.logs'),\n    '<li>appended line</li>'\n);\n")))),Object(o.b)("h3",{id:"prependcontent"},Object(o.b)("strong",{parentName:"h3"},"prependContent")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"This method inserts content as the first child of a specified element."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->prependContent(\n    'ul.logs',\n    '<li>prepended line</li>'\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.prependContent(\n    document.querySelector('ul.logs'),\n    '<li>prepended line</li>'\n);\n")))),Object(o.b)("h3",{id:"insertafter"},Object(o.b)("strong",{parentName:"h3"},"insertAfter")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"Use this method to insert content after a specified element."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->insertAfter(\n    'div.card',\n    '<div class=\"card\">content</div>'\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.insertAfter(\n    document.querySelector('div.card'),\n    '<div class=\"card\">content</div>'\n);\n")))),Object(o.b)("h3",{id:"insertbefore"},Object(o.b)("strong",{parentName:"h3"},"insertBefore")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"This method inserts content before a specified element."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->insertBefore(\n    'div.card',\n    '<div class=\"card\">content</div>'\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.insertAfter(\n    document.querySelector('div.card'),\n    '<div class=\"card\">content</div>'\n);\n")))),Object(o.b)("h3",{id:"remove"},Object(o.b)("strong",{parentName:"h3"},"remove")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"This method inserts content before a specified element."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->remove(\n    '#content[post-id=\"123\"]',\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.remove(\n    document.querySelector('#content[post-id=\"123\"]')\n);\n")))),Object(o.b)("h3",{id:"replace"},Object(o.b)("strong",{parentName:"h3"},"replace")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"This method replaces a specified element with new content."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->replace(\n    'div.content',\n    '<div class=\"content\">new content</div>',\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.replace(\n    document.querySelector('div.content'),\n    '<div class=\"content\">new content</div>'\n);\n")))),Object(o.b)("h3",{id:"eval"},Object(o.b)("strong",{parentName:"h3"},"eval")),Object(o.b)("ul",null,Object(o.b)("li",{parentName:"ul"},Object(o.b)("p",{parentName:"li"},"Use this method to evaluate JavaScript code provided as a string."),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"$response->eval(\n    'body',\n    'alert(\"Hello World!\");',\n);\n")),Object(o.b)("pre",{parentName:"li"},Object(o.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"DOM.eval(\n    document.querySelector('body'),\n    'alert(\"Hello World!\");'\n);\n")))),Object(o.b)("h2",{id:"live-example"},"Live Example"),Object(o.b)("p",null,"You can observe this API in action in the ",Object(o.b)("a",Object(r.a)({parentName:"p"},{href:"http://bigpipe.xf.cz/tutorial/basic-example"}),"demo page")," provided."))}s.isMDXComponent=!0},79:function(e,t,n){"use strict";n.d(t,"a",(function(){return b})),n.d(t,"b",(function(){return u}));var r=n(0),a=n.n(r);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function c(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function p(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?c(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):c(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function i(e,t){if(null==e)return{};var n,r,a=function(e,t){if(null==e)return{};var n,r,a={},o=Object.keys(e);for(r=0;r<o.length;r++)n=o[r],t.indexOf(n)>=0||(a[n]=e[n]);return a}(e,t);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);for(r=0;r<o.length;r++)n=o[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(a[n]=e[n])}return a}var l=a.a.createContext({}),s=function(e){var t=a.a.useContext(l),n=t;return e&&(n="function"==typeof e?e(t):p(p({},t),e)),n},b=function(e){var t=s(e.components);return a.a.createElement(l.Provider,{value:t},e.children)},d={inlineCode:"code",wrapper:function(e){var t=e.children;return a.a.createElement(a.a.Fragment,{},t)}},m=a.a.forwardRef((function(e,t){var n=e.components,r=e.mdxType,o=e.originalType,c=e.parentName,l=i(e,["components","mdxType","originalType","parentName"]),b=s(n),m=r,u=b["".concat(c,".").concat(m)]||b[m]||d[m]||o;return n?a.a.createElement(u,p(p({ref:t},l),{},{components:n})):a.a.createElement(u,p({ref:t},l))}));function u(e,t){var n=arguments,r=t&&t.mdxType;if("string"==typeof e||r){var o=n.length,c=new Array(o);c[0]=m;var p={};for(var i in t)hasOwnProperty.call(t,i)&&(p[i]=t[i]);p.originalType=e,p.mdxType="string"==typeof e?e:r,c[1]=p;for(var l=2;l<o;l++)c[l]=n[l];return a.a.createElement.apply(null,c)}return a.a.createElement.apply(null,n)}m.displayName="MDXCreateElement"}}]);