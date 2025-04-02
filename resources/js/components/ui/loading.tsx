import { PlaceholderPattern } from "./placeholder-pattern";

const Loading = () => {
    return (

        <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>

    );
};

export default Loading;
