/**
 * Server-provided boot data (SettingsPage inline script).
 */
const boot = window.lwSeo || {};

export const VERSION = boot.version || '';
export const NAMESPACE = boot.namespace || 'lw-seo/v1';
export const DOCS_URL = boot.docsUrl || 'https://lwplugins.com/docs/lw-seo/';
