import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Show({ invitation, accountExists }) {
    const { auth } = usePage().props;
    const loggedInAsInvitedEmail = auth?.user && auth.user.email.toLowerCase() === invitation.email.toLowerCase();

    function accept() {
        router.post(route('team.invitations.accept', [invitation.id, invitation.token]));
    }

    return (
        <GuestLayout>
            <Head title="Team Invitation" />

            <Card>
                <CardHeader>
                    <CardTitle className="text-foreground">Join {invitation.team_name}</CardTitle>
                    <CardDescription>
                        You've been invited by {invitation.invited_by} to join as a <strong>{invitation.role}</strong>.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {!invitation.is_pending ? (
                        <p className="text-sm text-destructive">This invitation has expired or was already used.</p>
                    ) : auth?.user ? (
                        loggedInAsInvitedEmail ? (
                            <Button onClick={accept} className="w-full">Accept &amp; join {invitation.team_name}</Button>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                You're logged in as {auth.user.email}, but this invitation was sent to {invitation.email}.
                                Log out and log back in with that address to accept.
                            </p>
                        )
                    ) : accountExists ? (
                        <Button asChild className="w-full">
                            <Link href={route('login')}>Log in as {invitation.email} to accept</Link>
                        </Button>
                    ) : (
                        <Button asChild className="w-full">
                            <Link href={route('register', { invitation: invitation.id, token: invitation.token })}>
                                Create an account to accept
                            </Link>
                        </Button>
                    )}
                </CardContent>
            </Card>
        </GuestLayout>
    );
}
