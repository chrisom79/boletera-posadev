import {MantineProvider, MantineThemeOverride, CSSVariablesResolver, MantineColorsTuple, ButtonProps, CheckboxProps, MantineTheme} from "@mantine/core";
import {PropsWithChildren, useMemo} from "react";
import {getContrastColor, hexToRgb} from "../../../utilites/themeUtils";

interface CheckoutThemeProviderProps {
    accentColor: string;
    mode: 'light' | 'dark';
}

/**
 * Fixed color palettes for checkout - these ensure good contrast and readability.
 * Users can only customize accent color, not the base palette.
 */
// Posadev 2026 — retro/pixel checkout palette (paper surface, xmas-green background)
const POSADEV_ACCENT = '#ff0a55';

const LIGHT_PALETTE = {
    surface: '#fef6e8',
    background: '#0e5c3a',
    textPrimary: '#0a0a0a',
    textSecondary: '#2a2a2a',
    textTertiary: '#2a2a2a',
    border: '#0a0a0a',
    // textPrimary/Secondary/Tertiary above are for text on the cream "surface"
    // (cards) and stay dark. Text rendered directly on the dark green
    // "background" (e.g. page headings outside any card) needs light text
    // instead, or it's unreadable against it.
    pageTextPrimary: '#ffffff',
    pageTextSecondary: '#e5e5e5',
};

const DARK_PALETTE = {
    surface: '#1f1f1f',
    background: '#121212',
    textPrimary: '#ffffff',
    textSecondary: '#a3a3a3',
    textTertiary: '#737373',
    border: '#333333',
    // Background is already near-black here, so page text matches textPrimary/Secondary.
    pageTextPrimary: '#ffffff',
    pageTextSecondary: '#a3a3a3',
};

/**
 * Creates a color palette that preserves the user's exact accent color.
 */
function createColorPalette(accentColor: string): MantineColorsTuple {
    const rgb = hexToRgb(accentColor);
    if (!rgb) {
        return ['#f3e8ff', '#e9d5ff', '#d8b4fe', '#c084fc', '#a855f7', '#9333ea', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95'];
    }

    const {r, g, b} = rgb;

    const lighten = (factor: number) => {
        const lr = Math.round(r + (255 - r) * factor);
        const lg = Math.round(g + (255 - g) * factor);
        const lb = Math.round(b + (255 - b) * factor);
        return `rgb(${lr}, ${lg}, ${lb})`;
    };

    const darken = (factor: number) => {
        const dr = Math.round(r * (1 - factor));
        const dg = Math.round(g * (1 - factor));
        const db = Math.round(b * (1 - factor));
        return `rgb(${dr}, ${dg}, ${db})`;
    };

    return [
        lighten(0.9),
        lighten(0.8),
        lighten(0.6),
        lighten(0.4),
        lighten(0.2),
        accentColor,
        accentColor,
        accentColor,
        darken(0.15),
        darken(0.3),
    ];
}

/**
 * Creates a Mantine theme with the user's accent color.
 */
function createCheckoutTheme(accentColor: string, mode: 'light' | 'dark'): MantineThemeOverride {
    const primaryColors = createColorPalette(accentColor);
    const contrastColor = getContrastColor(accentColor);

    return {
        primaryColor: 'primary',
        defaultRadius: 0,
        colors: {
            primary: primaryColors,
        },
        primaryShade: mode === 'dark' ? 6 : 7,
        components: {
            Button: {
                defaultProps: {
                    color: 'primary',
                },
                vars: (_theme: MantineTheme, props: ButtonProps) => {
                    if (props.variant === 'filled' || props.variant === undefined) {
                        return {
                            root: {
                                '--button-color': contrastColor,
                            },
                        };
                    }
                    return { root: {} };
                },
            },
            Checkbox: {
                defaultProps: {
                    color: 'primary',
                },
                vars: (_theme: MantineTheme, _props: CheckboxProps) => ({
                    root: {
                        '--checkbox-icon-color': contrastColor,
                    },
                }),
            },
            Switch: {
                defaultProps: {
                    color: 'primary',
                },
            },
            SegmentedControl: {
                defaultProps: {
                    color: 'primary',
                },
            },
            Badge: {
                defaultProps: {
                    color: 'primary',
                },
            },
        },
    };
}

/**
 * Creates CSS variables for checkout theming.
 * Surface, text, and border colors are FIXED based on light/dark mode.
 * Only accent color is customizable.
 */
function createCSSVariablesResolver(accentColor: string, mode: 'light' | 'dark'): CSSVariablesResolver {
    return () => {
        const palette = mode === 'light' ? LIGHT_PALETTE : DARK_PALETTE;
        const accentContrast = getContrastColor(accentColor);
        const rgb = hexToRgb(accentColor);

        const accentSoft = rgb
            ? `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${mode === 'light' ? 0.1 : 0.2})`
            : mode === 'light' ? 'rgba(139, 92, 246, 0.1)' : 'rgba(139, 92, 246, 0.2)';

        const accentMuted = rgb
            ? `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${mode === 'light' ? 0.6 : 0.7})`
            : mode === 'light' ? 'rgba(139, 92, 246, 0.6)' : 'rgba(139, 92, 246, 0.7)';

        return {
            variables: {
                // Accent colors (customizable)
                '--checkout-accent': accentColor,
                '--checkout-accent-contrast': accentContrast,
                '--checkout-accent-soft': accentSoft,
                '--checkout-accent-muted': accentMuted,

                // Fixed palette colors (not customizable - ensures readability)
                '--checkout-background': palette.background,
                '--checkout-surface': palette.surface,
                '--checkout-text-primary': palette.textPrimary,
                '--checkout-text-secondary': palette.textSecondary,
                '--checkout-text-tertiary': palette.textTertiary,
                '--checkout-border': palette.border,
                '--checkout-page-text-primary': palette.pageTextPrimary,
                '--checkout-page-text-secondary': palette.pageTextSecondary,

                // Override global --hi-text (set to accent in global.scss) and
                // Mantine's default text color to use fixed palette instead
                '--hi-text': palette.textPrimary,
                '--mantine-color-text': palette.textPrimary,
            },
            light: {},
            dark: {},
        };
    };
}

/**
 * CheckoutThemeProvider wraps checkout with themed Mantine components.
 *
 * Design philosophy:
 * - Checkout uses FIXED light/dark palettes for surfaces, text, and borders
 * - Only the accent color (buttons, links, highlights) is customizable
 * - This ensures readability and accessibility regardless of user choices
 */
export const CheckoutThemeProvider = ({
    children,
}: PropsWithChildren<CheckoutThemeProviderProps>) => {
    // Posadev 2026 — the checkout theme is locked to the retro palette regardless
    // of per-event settings, so accentColor/mode props are intentionally ignored.
    const theme = useMemo(
        () => createCheckoutTheme(POSADEV_ACCENT, 'light'),
        []
    );

    const cssVariablesResolver = useMemo(
        () => createCSSVariablesResolver(POSADEV_ACCENT, 'light'),
        []
    );

    return (
        <MantineProvider
            theme={theme}
            cssVariablesResolver={cssVariablesResolver}
            forceColorScheme="light"
        >
            {children}
        </MantineProvider>
    );
};

export default CheckoutThemeProvider;
