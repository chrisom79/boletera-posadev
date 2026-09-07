import {formatAddress} from "./addressUtilities.ts";

// Options matching one of these (case-insensitive, trimmed) are treated as an
// "other" choice on radio/checkbox questions: selecting one reveals a free-text
// field, and the submitted answer becomes "<option>: <free text>". Must match
// HiEvents\DomainObjects\QuestionDomainObject::OTHER_OPTION_LABELS on the backend.
const OTHER_OPTION_LABELS = ['otro', 'otra', 'other'];

export const isOtherOptionLabel = (label: string): boolean =>
    OTHER_OPTION_LABELS.includes(label.trim().toLowerCase());

export const findOtherOption = (options?: string[]): string | undefined =>
    options?.find(isOtherOptionLabel);

/**
 * Merges a question response's separate free-text field (collected when an
 * "other" option is selected) into the answer itself as "<option>: <text>",
 * which is the shape the backend validates and stores. Call this on every
 * question response right before submitting the order.
 */
export const mergeOtherAnswerIntoResponse = <T extends { answer?: unknown; other_text?: string }>(
    response: T
): Omit<T, 'other_text'> => {
    const {other_text: otherText, answer, ...rest} = response;
    const trimmedOtherText = otherText?.trim();

    if (!trimmedOtherText) {
        return {...rest, answer} as Omit<T, 'other_text'>;
    }

    if (Array.isArray(answer)) {
        return {
            ...rest,
            answer: answer.map((value: unknown) =>
                typeof value === 'string' && isOtherOptionLabel(value) ? `${value}: ${trimmedOtherText}` : value
            ),
        } as Omit<T, 'other_text'>;
    }

    if (typeof answer === 'string' && isOtherOptionLabel(answer)) {
        return {...rest, answer: `${answer}: ${trimmedOtherText}`} as Omit<T, 'other_text'>;
    }

    return {...rest, answer} as Omit<T, 'other_text'>;
};

export const isAddress = (obj: any) => {
    if (!obj || typeof obj !== 'object') return false;

    const addressFields = [
        'address_line_1',
        'address_line_2',
        'city',
        'state_or_region',
        'zip_or_postal_code',
        'country'
    ];

    return addressFields.some(field => field in obj);
};

export const formatAnswer = (answer: any) => {
    if (answer === null || answer === undefined) return '';

    if (Array.isArray(answer)) {
        return answer.join(", ");
    } else if (typeof answer === 'object') {
        if (isAddress(answer)) {
            return formatAddress(answer);
        } else {
            return JSON.stringify(answer);
        }
    } else {
        return answer.toString();
    }
};
