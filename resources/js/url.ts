/**
 * Links in widget settings: http(s), mailto, tel, relative paths and fragments
 * only. The same check as Javaabu\Widgets\Support\Url in PHP.
 */
const SAFE_URL = /^(https?:\/\/[^\s]+|mailto:[^\s]+|tel:[^\s]+|\/(?!\/)[^\s]*|#[^\s]*)$/i

export function isSafeUrl(url: unknown): url is string {
  return typeof url === "string" && SAFE_URL.test(url.trim())
}

/** The URL when it's safe, else null */
export function safeUrl(url: unknown): string | null {
  return isSafeUrl(url) ? url.trim() : null
}

/** Whether a link leaves the site, for rel="noopener" */
export function isExternalUrl(url: string): boolean {
  return /^https?:\/\//i.test(url)
}

/** Whether a setting has something to show, like PHP's filled() */
export function filled(value: unknown): boolean {
  if (value === null || value === undefined) return false
  if (typeof value === "string") return value.trim() !== ""
  if (Array.isArray(value)) return value.length > 0
  return true
}
