import LazyLoadScripts from "@otostogan/lazy-script";

history.scrollRestoration = "manual";
window.onbeforeunload = function () {
    window.scrollTo(0, 0);
};
LazyLoadScripts.run();
