(window.webpackJsonp=window.webpackJsonp||[]).push([[9],{67:function(e,t,n){"use strict";n.r(t),n.d(t,"frontMatter",(function(){return o})),n.d(t,"metadata",(function(){return l})),n.d(t,"rightToc",(function(){return c})),n.d(t,"default",(function(){return s}));var r=n(1),a=n(6),i=(n(0),n(79)),o={id:"getting_started",title:"Integrating into your app",sidebar_label:"Getting started"},l={unversionedId:"getting_started",id:"getting_started",isDocsHomePage:!1,title:"Integrating into your app",description:"\u2705 Requirements",source:"@site/..\\docs\\getting_started.md",slug:"/getting_started",permalink:"/bigpipe/docs/getting_started",version:"current",lastUpdatedBy:"Richard Dobro\u0148",lastUpdatedAt:1685255164,sidebar_label:"Getting started",sidebar:"docs",next:{title:"How it works",permalink:"/bigpipe/docs/how_it_works"}},c=[{value:"\u2705 Requirements",id:"-requirements",children:[]},{value:"\ud83d\udce6 Installing",id:"-installing",children:[{value:"1. Install composer package",id:"1-install-composer-package",children:[]},{value:"2. Install npm package",id:"2-install-npm-package",children:[]},{value:"3. Modify your entrypoint",id:"3-modify-your-entrypoint",children:[]},{value:"4. Add this code to page footer",id:"4-add-this-code-to-page-footer",children:[]}]}],p={rightToc:c};function s(e){var t=e.components,n=Object(a.a)(e,["components"]);return Object(i.b)("wrapper",Object(r.a)({},p,n,{components:t,mdxType:"MDXLayout"}),Object(i.b)("h2",{id:"-requirements"},"\u2705 Requirements"),Object(i.b)("ul",null,Object(i.b)("li",{parentName:"ul"},"PHP 7.1 or higher"),Object(i.b)("li",{parentName:"ul"},"Webpack")),Object(i.b)("h2",{id:"-installing"},"\ud83d\udce6 Installing"),Object(i.b)("p",null,"Follow these steps to integrate BigPipe into your application:"),Object(i.b)("h3",{id:"1-install-composer-package"},"1. Install composer package"),Object(i.b)("p",null,"Open your terminal and run the following command to install the ",Object(i.b)("inlineCode",{parentName:"p"},"Composer")," package:"),Object(i.b)("pre",null,Object(i.b)("code",Object(r.a)({parentName:"pre"},{className:"language-shell"}),"$ composer require richarddobron/bigpipe\n")),Object(i.b)("h3",{id:"2-install-npm-package"},"2. Install npm package"),Object(i.b)("p",null,"In your terminal, install the ",Object(i.b)("inlineCode",{parentName:"p"},"npm")," package for BigPipe:"),Object(i.b)("pre",null,Object(i.b)("code",Object(r.a)({parentName:"pre"},{className:"language-shell"}),"$ npm install bigpipe-util\n")),Object(i.b)("h3",{id:"3-modify-your-entrypoint"},"3. Modify your entrypoint"),Object(i.b)("p",null,"In your entrypoint file (e.g., ",Object(i.b)("inlineCode",{parentName:"p"},"/path/to/resources/js/app.js"),"), add the following code to set up BigPipe:"),Object(i.b)("pre",null,Object(i.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"import Primer from 'bigpipe-util/src/Primer';\n\nPrimer();\n\nwindow.require = (modulePath) => {\n    return modulePath.startsWith('bigpipe-util/')\n        ? require('bigpipe-util/' + modulePath.substring(13) + '.js').default\n        : require('./' + modulePath).default;\n};\n")),Object(i.b)("p",null,"Create your first module in the same directory (e.g., ",Object(i.b)("inlineCode",{parentName:"p"},"/path/to/resources/js/MyModule.js"),"):"),Object(i.b)("pre",null,Object(i.b)("code",Object(r.a)({parentName:"pre"},{className:"language-javascript"}),"export default class MyModule {\n    init(...args) {\n        console.log('Hello world!');\n        console.warning(args);\n    }\n}\n")),Object(i.b)("p",null,"call it using:"),Object(i.b)("pre",null,Object(i.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),"<?php\n$asyncResponse = new \\dobron\\BigPipe\\AsyncResponse();\n\n$asyncResponse->bigPipe()->require(\"require('MyModule').init()\", [\n    'first argument',\n    'second argument',\n    ...\n]);\n")),Object(i.b)("h3",{id:"4-add-this-code-to-page-footer"},"4. Add this code to page footer"),Object(i.b)("p",null,"In your page footer, add the following code to set up BigPipe:"),Object(i.b)("pre",null,Object(i.b)("code",Object(r.a)({parentName:"pre"},{className:"language-php"}),'<script>\n    (new (require("bigpipe-util/src/ServerJS"))).handle(<?=json_encode(\\dobron\\BigPipe\\BigPipe::jsmods())?>);\n<\/script>\n')),Object(i.b)("p",null,"\ud83c\udf89 Congratulations! You've successfully integrated BigPipe into your application. Enjoy improved web performance and user experiences."),Object(i.b)("p",null,Object(i.b)("em",{parentName:"p"},"Feel free to adjust the wording and formatting as needed to match your documentation's style.")))}s.isMDXComponent=!0},79:function(e,t,n){"use strict";n.d(t,"a",(function(){return u})),n.d(t,"b",(function(){return g}));var r=n(0),a=n.n(r);function i(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function o(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function l(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?o(Object(n),!0).forEach((function(t){i(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):o(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function c(e,t){if(null==e)return{};var n,r,a=function(e,t){if(null==e)return{};var n,r,a={},i=Object.keys(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||(a[n]=e[n]);return a}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(a[n]=e[n])}return a}var p=a.a.createContext({}),s=function(e){var t=a.a.useContext(p),n=t;return e&&(n="function"==typeof e?e(t):l(l({},t),e)),n},u=function(e){var t=s(e.components);return a.a.createElement(p.Provider,{value:t},e.children)},d={inlineCode:"code",wrapper:function(e){var t=e.children;return a.a.createElement(a.a.Fragment,{},t)}},b=a.a.forwardRef((function(e,t){var n=e.components,r=e.mdxType,i=e.originalType,o=e.parentName,p=c(e,["components","mdxType","originalType","parentName"]),u=s(n),b=r,g=u["".concat(o,".").concat(b)]||u[b]||d[b]||i;return n?a.a.createElement(g,l(l({ref:t},p),{},{components:n})):a.a.createElement(g,l({ref:t},p))}));function g(e,t){var n=arguments,r=t&&t.mdxType;if("string"==typeof e||r){var i=n.length,o=new Array(i);o[0]=b;var l={};for(var c in t)hasOwnProperty.call(t,c)&&(l[c]=t[c]);l.originalType=e,l.mdxType="string"==typeof e?e:r,o[1]=l;for(var p=2;p<i;p++)o[p]=n[p];return a.a.createElement.apply(null,o)}return a.a.createElement.apply(null,n)}b.displayName="MDXCreateElement"}}]);