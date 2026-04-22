window.addEventListener(
  "load",
  function () {
    const elem = document.getElementById("logout_suggestion");
    let url = elem.getAttribute("href");
    url = replaceUrlParam(url, "destination", window.location.pathname);
    elem.setAttribute("href", url);
  },
  false,
);

// @see https://stackoverflow.com/a/20420424
function replaceUrlParam(url, paramName, paramValue) {
  if (paramValue == null) {
    paramValue = "";
  }
  const pattern = new RegExp("\\b(" + paramName + "=).*?(&|#|$)");
  if (url.search(pattern) >= 0) {
    return url.replace(pattern, "$1" + paramValue + "$2");
  }
  url = url.replace(/[?#]$/, "");
  return (
    url + (url.indexOf("?") > 0 ? "&" : "?") + paramName + "=" + paramValue
  );
}
