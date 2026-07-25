import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Index({ reports = [], websites = [], canExportCsv, canExportPdf }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold">Reports</h2>
                    <Button variant="outline" disabled={!canExportCsv} asChild={canExportCsv}>
                        {canExportCsv ? <a href={route('reports.export-csv')}>Export CSV</a> : <span>Export CSV</span>}
                    </Button>
                </div>
            }
        >
            <Head title="Reports" />

            {websites.length > 0 && (
                <Card className="mb-6">
                    <CardHeader><CardTitle>Executive summaries</CardTitle></CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {websites.map((w) => (
                            <Button key={w.id} variant="outline" size="sm" disabled={!canExportPdf} asChild={canExportPdf}>
                                {canExportPdf ? (
                                    <a href={route('reports.export-pdf', [w.id, 'executive'])}>
                                        <Download className="h-3.5 w-3.5" /> {w.name}
                                    </a>
                                ) : (
                                    <span>{w.name} (upgrade for PDF)</span>
                                )}
                            </Button>
                        ))}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader><CardTitle>Recent scans across all websites</CardTitle></CardHeader>
                <CardContent>
                    {reports.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No scans have run yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Website</th>
                                    <th className="py-2">Type</th>
                                    <th className="py-2">Score</th>
                                    <th className="py-2">Date</th>
                                    <th className="py-2" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {reports.map((r, i) => (
                                    <tr key={i}>
                                        <td className="py-2">{r.website}</td>
                                        <td className="py-2"><Badge variant="outline">{r.type}</Badge></td>
                                        <td className="py-2">{r.score ?? '—'}</td>
                                        <td className="py-2 text-muted-foreground">{new Date(r.created_at).toLocaleString()}</td>
                                        <td className="py-2 text-right">
                                            {canExportPdf && (
                                                <a
                                                    href={route('reports.export-pdf', [r.website_id, r.type])}
                                                    className="text-primary hover:underline"
                                                >
                                                    PDF
                                                </a>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
