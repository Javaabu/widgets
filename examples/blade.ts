import "../resources/js/blade/admin"

const log = document.getElementById("log")!

// No server here: answer the order request and show what was sent
window.fetch = async (input, init) => {
  const body = init?.body instanceof FormData ? init.body.getAll("widgets[]") : []
  log.textContent += `PUT ${String(input)} widgets=${JSON.stringify(body)}\n`
  return new Response(JSON.stringify({ message: "Widget order saved." }), { status: 200 })
}

document.getElementById("direction")!.addEventListener("click", () => {
  const rtl = document.documentElement.dir !== "rtl"
  document.documentElement.dir = rtl ? "rtl" : "ltr"
  document.documentElement.lang = rtl ? "dv" : "en"
})
