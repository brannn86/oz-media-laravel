import { LocationCombobox } from '@/components/station/location-combobox';
import { OzField, ozInputClass } from '@/components/station/oz-field';
import { cn } from '@/lib/utils';

/** Same values as App\Enums\AgeRange and App\Enums\Gender. */
const AGE_RANGES = [
    ['under_18', 'Under 18'],
    ['18_24', '18–24'],
    ['25_34', '25–34'],
    ['35_44', '35–44'],
    ['45_54', '45–54'],
    ['55_plus', '55 or older'],
    ['prefer_not_to_say', 'Prefer not to say'],
] as const;

const GENDERS = [
    ['female', 'Female'],
    ['male', 'Male'],
    ['prefer_not_to_say', 'Prefer not to say'],
] as const;

/**
 * Age range, gender and location, for sign-up (required) and the profile
 * page. Field names match the server: age_range, gender, location.
 */
export function ListenerDetailsFields({
    idPrefix,
    errors,
    required = false,
    defaults = {},
}: {
    idPrefix: string;
    errors: Partial<Record<'age_range' | 'gender' | 'location', string>>;
    required?: boolean;
    defaults?: {
        age_range?: string | null;
        gender?: string | null;
        location?: string | null;
    };
}) {
    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <OzField
                    id={`${idPrefix}-age`}
                    label="Age"
                    error={errors.age_range}
                >
                    <select
                        id={`${idPrefix}-age`}
                        name="age_range"
                        required={required}
                        defaultValue={defaults.age_range ?? ''}
                        aria-invalid={Boolean(errors.age_range)}
                        className={cn(ozInputClass, 'w-full')}
                    >
                        <option value="" disabled={required}>
                            Choose…
                        </option>
                        {AGE_RANGES.map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </OzField>
                <OzField
                    id={`${idPrefix}-gender`}
                    label="Gender"
                    error={errors.gender}
                >
                    <select
                        id={`${idPrefix}-gender`}
                        name="gender"
                        required={required}
                        defaultValue={defaults.gender ?? ''}
                        aria-invalid={Boolean(errors.gender)}
                        className={cn(ozInputClass, 'w-full')}
                    >
                        <option value="" disabled={required}>
                            Choose…
                        </option>
                        {GENDERS.map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </OzField>
            </div>
            <OzField
                id={`${idPrefix}-location`}
                label="Where do you live?"
                error={errors.location}
            >
                <LocationCombobox
                    id={`${idPrefix}-location`}
                    required={required}
                    defaultValue={defaults.location ?? ''}
                    invalid={Boolean(errors.location)}
                />
            </OzField>
        </>
    );
}
