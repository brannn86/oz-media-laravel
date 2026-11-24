import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * The station site's popup: OZ colours and type, scrolls inside itself on
 * small screens, closes with Esc, the X or a tap outside.
 */
export function OzDialog({
    open,
    onOpenChange,
    title,
    description,
    media,
    children,
    className,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    /** Short line under the title; also the dialog's accessible description. */
    description?: ReactNode;
    /** Optional image area on top (or left on wide screens). */
    media?: ReactNode;
    children?: ReactNode;
    className?: string;
}) {
    return (
        <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
            <DialogPrimitive.Portal>
                <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-oz-black/70 backdrop-blur-sm data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0" />
                <DialogPrimitive.Content
                    className={cn(
                        'fixed top-1/2 left-1/2 z-50 grid max-h-[calc(100svh-2rem)] w-[calc(100vw-2rem)] max-w-3xl -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-[2rem] bg-oz-white font-oz text-oz-black shadow-2xl focus:outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95',
                        // On phones the image sits on top and the text scrolls below it.
                        media &&
                            'grid-rows-[auto_minmax(0,1fr)] md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:grid-rows-1',
                        className,
                    )}
                >
                    {media && (
                        <div className="relative aspect-[4/3] bg-oz-yellow md:aspect-auto">
                            {media}
                        </div>
                    )}
                    <div className="min-h-0 overflow-y-auto p-6 sm:p-8">
                        <DialogPrimitive.Title className="pr-10 font-oz-display text-3xl leading-[0.95] font-extrabold tracking-tight text-balance sm:text-4xl">
                            {title}
                        </DialogPrimitive.Title>
                        {description ? (
                            <DialogPrimitive.Description className="mt-2 text-base font-semibold text-oz-ink/70">
                                {description}
                            </DialogPrimitive.Description>
                        ) : (
                            <DialogPrimitive.Description className="sr-only">
                                {title}
                            </DialogPrimitive.Description>
                        )}
                        {children}
                    </div>
                    <DialogPrimitive.Close
                        className="absolute top-4 right-4 grid size-10 place-items-center rounded-full bg-oz-black text-oz-white transition-transform hover:scale-105 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                        aria-label="Close"
                    >
                        <X className="size-5" />
                    </DialogPrimitive.Close>
                </DialogPrimitive.Content>
            </DialogPrimitive.Portal>
        </DialogPrimitive.Root>
    );
}

/** A labelled block inside a dialog ("Schedule", "Hosts", …). */
export function DialogSection({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="mt-6">
            <h3 className="text-sm font-bold tracking-wide text-oz-black/60 uppercase">
                {title}
            </h3>
            <div className="mt-2">{children}</div>
        </section>
    );
}
