import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type NotificationRow = {
    id: string;
    title: string;
    body: string;
    url: string | null;
    read: boolean;
    created_at: string;
};

export default function NotificationsIndex({
    notifications,
}: {
    notifications: NotificationRow[];
}) {
    return (
        <>
            <Head title="Notifications" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Notifications"
                        description="Demandes, décisions, bulletins et rappels de contrat."
                    />
                    <Button
                        variant="outline"
                        onClick={() => router.post('/notifications/tout-lire')}
                    >
                        Tout marquer comme lu
                    </Button>
                </div>
                <ul className="space-y-3">
                    {notifications.length === 0 && (
                        <li className="text-muted-foreground text-sm">
                            Aucune notification.
                        </li>
                    )}
                    {notifications.map((notification) => (
                        <li
                            key={notification.id}
                            className="rounded-xl border p-4"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="font-medium">
                                        {notification.title}
                                        {!notification.read && (
                                            <span className="ml-2 text-xs text-amber-700">
                                                Non lue
                                            </span>
                                        )}
                                    </p>
                                    <p className="mt-1 text-sm">
                                        {notification.body}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {notification.created_at}
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            `/notifications/${notification.id}/lire`,
                                        )
                                    }
                                >
                                    Ouvrir
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifications', href: '/notifications' }],
};
