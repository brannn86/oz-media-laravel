import { Check, MapPin } from 'lucide-react';
import { useId, useState } from 'react';
import { ozInputClass } from '@/components/station/oz-field';
import { listedLocation, suggestLocations } from '@/lib/locations';
import { cn } from '@/lib/utils';

/**
 * A text field with suggested places under it (ARIA combobox). Listeners
 * can type anything, but are nudged to pick a suggestion, which is what
 * makes "listeners by area" countable. The value goes out as `name`.
 */
export function LocationCombobox({
    id,
    name = 'location',
    defaultValue = '',
    required = false,
    invalid = false,
}: {
    id: string;
    name?: string;
    defaultValue?: string;
    required?: boolean;
    invalid?: boolean;
}) {
    const listId = useId();
    const [value, setValue] = useState(defaultValue);
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(-1);

    const suggestions = suggestLocations(value);
    const listed = listedLocation(value);
    const showList = open && suggestions.length > 0 && listed !== value;

    const pick = (place: string) => {
        setValue(place);
        setOpen(false);
        setActive(-1);
    };

    return (
        <div>
            <div className="relative">
                <input
                    id={id}
                    name={name}
                    value={value}
                    required={required}
                    autoComplete="off"
                    placeholder="Start typing your city or regency"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded={showList}
                    aria-controls={listId}
                    aria-activedescendant={
                        showList && active >= 0
                            ? `${listId}-${active}`
                            : undefined
                    }
                    aria-describedby={`${id}-hint`}
                    aria-invalid={invalid}
                    onChange={(event) => {
                        setValue(event.target.value);
                        setOpen(true);
                        setActive(-1);
                    }}
                    onFocus={() => setOpen(true)}
                    onBlur={() => {
                        setOpen(false);
                        // Typed a listed place in other casing: use its spelling.
                        const match = listedLocation(value);

                        if (match) {
                            setValue(match);
                        }
                    }}
                    onKeyDown={(event) => {
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            setOpen(true);
                            setActive((i) =>
                                Math.min(i + 1, suggestions.length - 1),
                            );
                        } else if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            setActive((i) => Math.max(i - 1, 0));
                        } else if (
                            event.key === 'Enter' &&
                            showList &&
                            active >= 0
                        ) {
                            event.preventDefault();
                            pick(suggestions[active]);
                        } else if (event.key === 'Escape') {
                            setOpen(false);
                        }
                    }}
                    className={cn(ozInputClass, 'w-full pl-11')}
                />
                <MapPin
                    className="pointer-events-none absolute top-3.5 left-4 size-5 text-oz-black/40"
                    aria-hidden
                />

                {showList && (
                    <ul
                        id={listId}
                        role="listbox"
                        aria-label="Suggested places"
                        className="absolute inset-x-0 top-full z-20 mt-1 max-h-64 overflow-y-auto rounded-xl border-2 border-oz-black/15 bg-oz-white py-1 shadow-lg"
                    >
                        {suggestions.map((place, index) => (
                            <li
                                key={place}
                                id={`${listId}-${index}`}
                                role="option"
                                aria-selected={index === active}
                                // Before the input's blur, so the pick lands.
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    pick(place);
                                }}
                                onMouseEnter={() => setActive(index)}
                                className={cn(
                                    'cursor-pointer px-4 py-2.5 text-sm',
                                    index === active && 'bg-oz-butter',
                                )}
                            >
                                {place}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <p
                id={`${id}-hint`}
                className={cn(
                    'mt-1.5 flex items-start gap-1.5 text-sm',
                    listed ? 'text-oz-black/70' : 'text-oz-ink',
                )}
            >
                {listed ? (
                    <>
                        <Check className="mt-0.5 size-4 shrink-0" aria-hidden />
                        From the list, thanks!
                    </>
                ) : value.trim() === '' ? (
                    'Start typing, then pick your city or regency from the list.'
                ) : suggestions.length > 0 ? (
                    'Please pick a suggestion if one fits: it helps us know where our listeners are.'
                ) : (
                    "Not on the list? That's fine, we'll keep what you typed."
                )}
            </p>
        </div>
    );
}
