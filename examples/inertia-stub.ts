/** Stands in for @inertiajs/react in the examples */
export const router = {
  put(url: string, data: unknown) {
    console.info("router.put", url, data)
    document.dispatchEvent(new CustomEvent("example:put", { detail: { url, data } }))
  },
}
