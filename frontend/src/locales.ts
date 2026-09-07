import {i18n} from "@lingui/core";

export type SupportedLocales =
    "en"
    | "de"
    | "fr"
    | "it"
    | "nl"
    | "pt"
    | "es"
    | "zh-cn"
    | "pt-br"
    | "vi"
    | "zh-hk"
    | "tr"
    | "hu"
    | "pl"
    | "se"
    | "sk"
    | "el";

export const availableLocales = ["en", "de", "fr", "it", "nl", "pt", "es", "zh-cn", "zh-hk", "pt-br", "vi", "tr", "hu", "pl", "se", "sk", "el"];

export const localeToFlagEmojiMap: Record<SupportedLocales, string> = {
    en: '🇬🇧',
    de: '🇩🇪',
    fr: '🇫🇷',
    it: '🇮🇹',
    nl: '🇳🇱',
    pt: '🇵🇹',
    es: '🇪🇸',
    "zh-cn": '🇨🇳',
    "zh-hk": '🇭🇰',
    "pt-br": '🇧🇷',
    vi: '🇻🇳',
    tr: '🇹🇷',
    hu: '🇭🇺',
    pl: '🇵🇱',
    se: '🇸🇪',
    sk: '🇸🇰',
    el: '🇬🇷',
};

export const localeToNameMap: Record<SupportedLocales, string> = {
    en: `English`,
    de: `German`,
    fr: `French`,
    it: `Italian`,
    nl: `Dutch`,
    pt: `Portuguese`,
    es: `Spanish`,
    "zh-cn": `Chinese`,
    "zh-hk": `Cantonese`,
    "pt-br": `Portuguese (Brazil)`,
    vi: `Vietnamese`,
    tr: `Turkish`,
    hu: `Hungarian`,
    pl: `Polish`,
    se: `Swedish`,
    sk: `Slovak`,
    el: `Greek`,
};

export const getLocaleName = (locale: SupportedLocales) => {
    return localeToNameMap[locale];
}

export const getClientLocale = () => {
    // Default to Spanish rather than auto-detecting from the browser's language:
    // this deployment serves a Spanish-speaking community, and a visitor with an
    // English-language browser should still see the site in Spanish by default.
    // The LanguageSwitcher component sets the "locale" cookie for anyone who
    // explicitly wants a different language, which always takes priority.
    // Must match the default in entry.server.tsx's getLocale() so client-side
    // hydration doesn't mismatch the server-rendered locale.
    if (typeof window !== "undefined") {
        const storedLocale = document
            .cookie
            .split(";")
            .find((c) => c.includes("locale="))
            ?.split("=")[1];

        if (storedLocale) {
            return getSupportedLocale(storedLocale);
        }

        return "es";
    }

    return "es";
};

export async function dynamicActivateLocale(locale: string) {
    try {
        locale = availableLocales.includes(locale) ? locale : "en";
        const module = (await import(`./locales/${locale}.po`));
        i18n.load(locale, module.messages);
        i18n.activate(locale);
    } catch (error) {
        console.error("Error loading locale:", error);
        // i18n.activate("en");
    }
}

export const getSupportedLocale = (userLocale: string) => {
    const normalizedLocale = userLocale.toLowerCase();

    if (availableLocales.includes(normalizedLocale)) {
        return normalizedLocale;
    }

    const mainLanguage = normalizedLocale.split('-')[0];
    const mainLocale = availableLocales.find(locale => locale.startsWith(mainLanguage));
    if (mainLocale) {
        return mainLocale;
    }

    return "en";
};
