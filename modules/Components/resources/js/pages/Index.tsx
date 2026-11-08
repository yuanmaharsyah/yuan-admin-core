import { AreaChartCard } from '@admin-core/components/admin/AreaChartCard';
import { BarChartCard } from '@admin-core/components/admin/BarChartCard';
import {
    HorizontalBarChartCard,
    LineChartCard,
    PieChartCard,
    RadarChartCard,
    RadialChartCard,
    StackedBarChartCard,
} from '@admin-core/components/admin/ChartCards';
import { ChangePill } from '@admin-core/components/admin/ChangePill';
import { ConfirmDialog } from '@admin-core/components/admin/ConfirmDialog';
import { DataTable } from '@admin-core/components/admin/DataTable';
import type { DataTableColumn } from '@admin-core/components/admin/DataTable';
import { DateField } from '@admin-core/components/admin/DateField';
import { DonutCard } from '@admin-core/components/admin/DonutCard';
import { EmptyState } from '@admin-core/components/admin/EmptyState';
import { KanbanBoard } from '@admin-core/components/admin/KanbanBoard';
import { ListCard } from '@admin-core/components/admin/ListCard';
import { CurrencyField } from '@admin-core/components/admin/NumberField';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { SelectField } from '@admin-core/components/admin/SelectField';
import { StatTile } from '@admin-core/components/admin/StatTile';
import { StatusBadge } from '@admin-core/components/admin/StatusBadge';
import { SwitchField } from '@admin-core/components/admin/SwitchField';
import { Tabs } from '@admin-core/components/admin/Tabs';
import { TextField } from '@admin-core/components/admin/TextField';
import { Badge } from '@admin-core/components/ui/badge';
import { Button } from '@admin-core/components/ui/button';
import { formatCurrency, formatLocalDate } from '@admin-core/lib/format';
import { Head } from '@inertiajs/react';
import { Check, Copy, Inbox, Plus, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';

type Page = Yuan.AdminCore.Modules.Components.Data.ComponentsPageData;
type Row = Yuan.AdminCore.Modules.Components.Data.SampleRowData;

/*
 * Komponen (DEVELOPER.md R21): the house style, live. Every value below is a
 * documentation fixture, never app data. The same catalogue, for AI agents,
 * is the `admin-ui-components` Boost skill; keep both in step.
 */

const DAYS = Array.from({ length: 14 }, (_, index) => {
    const day = new Date(Date.UTC(2026, 9, 26 + index));

    return day.toISOString().slice(0, 10);
});
const WAVE = [12, 18, 9, 22, 30, 17, 25, 14, 28, 35, 21, 26, 19, 31];

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'].map(
    (label, index) => ({
        label,
        masuk: [42, 58, 51, 66, 72, 69][index] ?? 0,
        keluar: [30, 35, 40, 38, 49, 44][index] ?? 0,
    }),
);
const SHARE = [
    { label: 'Bandung', nilai: 38 },
    { label: 'Surabaya', nilai: 27 },
    { label: 'Jakarta', nilai: 21 },
    { label: 'Medan', nilai: 14 },
];
const SKILLS = ['Cepat', 'Tepat', 'Ramah', 'Rapi', 'Tuntas'].map(
    (label, index) => ({
        label,
        tim_a: [80, 65, 90, 70, 75][index] ?? 0,
        tim_b: [60, 85, 70, 80, 65][index] ?? 0,
    }),
);
const IN_OUT = [
    { key: 'masuk', label: 'Masuk' },
    { key: 'keluar', label: 'Keluar' },
];

const SECTIONS = [
    ['tombol', 'Tombol'],
    ['badge', 'Badge'],
    ['form', 'Form'],
    ['kpi', 'KPI'],
    ['grafik', 'Grafik'],
    ['grafik-lain', 'Grafik lain'],
    ['daftar', 'Daftar'],
    ['tabel', 'Tabel'],
    ['dialog', 'Dialog'],
    ['kosong', 'Keadaan kosong'],
    ['tab', 'Tab'],
    ['kanban', 'Kanban'],
    ['menu', 'Menu'],
] as const;

export default function Index({ table, state }: Page) {
    const [text, setText] = useState('');
    const [choice, setChoice] = useState('');
    const [date, setDate] = useState<string | null>(null);
    const [amount, setAmount] = useState<number | null>(1250000);
    const [on, setOn] = useState(true);
    const [confirm, setConfirm] = useState(false);
    const [tab, setTab] = useState('detail');
    // The inline-edit example keeps its changes on this page only.
    const [edits, setEdits] = useState<Record<number, Partial<Row>>>({});
    const edited = (row: Row): Row => ({ ...row, ...edits[row.id] });
    const keep = (
        row: Row,
        change: Partial<Row>,
        done: (saved: boolean) => void,
    ) => {
        setEdits({ ...edits, [row.id]: { ...edits[row.id], ...change } });
        done(true);
    };

    const columns: DataTableColumn<Row>[] = [
        {
            key: 'name',
            label: 'Pelanggan',
            className: 'font-medium',
            cell: (row) => edited(row).name,
            edit: {
                type: 'text',
                value: (row) => edited(row).name,
                save: (row, value, done) => keep(row, { name: value }, done),
            },
        },
        { key: 'city', label: 'Kota' },
        {
            key: 'date',
            label: 'Tanggal',
            cell: (row) => formatLocalDate(row.date),
        },
        {
            key: 'status',
            label: 'Status',
            edit: {
                type: 'select',
                options: [
                    { value: 'paid', label: 'Lunas' },
                    { value: 'sent', label: 'Terkirim' },
                    { value: 'draft', label: 'Draf' },
                ],
                value: (row) => edited(row).status,
                save: (row, value, done) =>
                    keep(
                        row,
                        {
                            status:
                                value === 'paid' || value === 'sent'
                                    ? value
                                    : 'draft',
                        },
                        done,
                    ),
            },
            cell: (row) =>
                edited(row).status === 'paid' ? (
                    <StatusBadge tone="success" label="Lunas" />
                ) : edited(row).status === 'sent' ? (
                    <StatusBadge tone="info" label="Terkirim" />
                ) : (
                    <StatusBadge label="Draf" />
                ),
        },
        {
            key: 'total',
            label: 'Total',
            align: 'right',
            cell: (row) => formatCurrency(row.total),
        },
    ];

    return (
        <>
            <Head title="Komponen" />
            <div className="flex flex-col gap-6">
                <PageHeader
                    title="Komponen"
                    description="Gaya rumah panel ini: pakai komponen di bawah, jangan membuat ulang. Versi untuk AI: skill admin-ui-components."
                />
                <div className="flex gap-8">
                    <nav
                        aria-label="Daftar komponen"
                        className="sticky top-0 hidden w-44 shrink-0 self-start xl:block"
                    >
                        <ul className="m-0 flex list-none flex-col gap-0.5 p-0">
                            {SECTIONS.map(([id, label]) => (
                                <li key={id}>
                                    <a
                                        href={`#${id}`}
                                        className="block rounded-md px-3 py-1.5 text-body text-muted-foreground no-underline transition-colors duration-150 hover:bg-accent hover:text-foreground"
                                    >
                                        {label}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <div className="flex min-w-0 flex-1 flex-col gap-10">
                        <Section
                            id="tombol"
                            title="Tombol"
                            text="Button (shadcn): default, outline, secondary, ghost, destructive. Tombol yang memanggil server memakai loading (useAction)."
                            code={`import { Button } from '@admin-core/components/ui/button';

<Button><Plus /> Tambah</Button>
<Button variant="outline">Batal</Button>
<Button variant="destructive" loading={remove.pending}>Hapus</Button>`}
                        >
                            <div className="flex flex-wrap items-center gap-3">
                                <Button>
                                    <Plus /> Tambah
                                </Button>
                                <Button variant="outline">Batal</Button>
                                <Button variant="secondary">Sekunder</Button>
                                <Button variant="ghost">Ghost</Button>
                                <Button variant="destructive">
                                    <Trash2 /> Hapus
                                </Button>
                                <Button loading>Menyimpan</Button>
                                <Button size="sm" variant="outline">
                                    Kecil
                                </Button>
                            </div>
                        </Section>

                        <Section
                            id="badge"
                            title="Badge"
                            text="Badge untuk label pendek (menu 'Baru'), StatusBadge untuk status dengan titik, ChangePill untuk persen perubahan."
                            code={`import { Badge } from '@admin-core/components/ui/badge';
import { StatusBadge } from '@admin-core/components/admin/StatusBadge';
import { ChangePill } from '@admin-core/components/admin/ChangePill';

<Badge>Baru</Badge>
<StatusBadge tone="success" label="Lunas" />
<ChangePill change={18} />`}
                        >
                            <div className="flex flex-wrap items-center gap-3">
                                <Badge>Baru</Badge>
                                <Badge variant="secondary">Sekunder</Badge>
                                <Badge variant="outline">Outline</Badge>
                                <StatusBadge tone="success" label="Lunas" />
                                <StatusBadge tone="info" label="Terkirim" />
                                <StatusBadge
                                    tone="warning"
                                    label="Jatuh tempo"
                                />
                                <StatusBadge tone="destructive" label="Batal" />
                                <StatusBadge label="Draf" />
                                <ChangePill change={18} />
                                <ChangePill change={-5} />
                            </div>
                        </Section>

                        <Section
                            id="form"
                            title="Form"
                            text="Setiap kontrol berada di dalam FormField (label, bantuan, error). Halaman form memakai useAdminForm + FormFooter (R19)."
                            code={`<TextField name="number" label="Nomor" value={form.data.number ?? ''}
    onChange={(v) => form.setData('number', v)} error={form.errors.number} required />
<SelectField name="status" label="Status" options={statuses} value={...} onChange={...} />
<DateField name="issued_on" label="Tanggal" value={...} onChange={...} />
<CurrencyField name="total" label="Total" value={...} onChange={...} />
<SwitchField name="send_reminder" label="Kirim pengingat" value={...} onChange={...} />`}
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <TextField
                                    name="demo_text"
                                    label="Nama"
                                    value={text}
                                    onChange={setText}
                                    help="Teks bantuan di bawah kontrol."
                                    required
                                />
                                <SelectField
                                    name="demo_select"
                                    label="Status"
                                    value={choice}
                                    onChange={setChoice}
                                    placeholder="Pilih…"
                                    options={[
                                        { value: 'paid', label: 'Lunas' },
                                        { value: 'sent', label: 'Terkirim' },
                                    ]}
                                />
                                <DateField
                                    name="demo_date"
                                    label="Tanggal"
                                    value={date}
                                    onChange={setDate}
                                />
                                <CurrencyField
                                    name="demo_amount"
                                    label="Total"
                                    value={amount}
                                    onChange={setAmount}
                                    error="Contoh pesan error."
                                />
                                <SwitchField
                                    name="demo_switch"
                                    label="Kirim pengingat"
                                    value={on}
                                    onChange={setOn}
                                />
                            </div>
                        </Section>

                        <Section
                            id="kpi"
                            title="KPI"
                            text="StatTile: judul + ikon bulat, angka besar, ChangePill + caption, tombol aksi. Di Beranda datang dari widget 'stat'."
                            code={`<StatTile icon="file-plus" label="Invoice baru" value={23}
    change={18} caption="dibanding periode sebelumnya"
    action={<SeeAll href={index().url} label={t('widgets.see_all')} />} />`}
                        >
                            <div className="grid gap-6 md:grid-cols-3">
                                <StatTile
                                    icon="file-plus"
                                    label="Invoice baru"
                                    value={23}
                                    change={18}
                                    caption="dibanding periode sebelumnya"
                                />
                                <StatTile
                                    icon="wallet"
                                    label="Pendapatan"
                                    value={87860000}
                                    format="currency"
                                    change={-5}
                                />
                                <StatTile
                                    icon="activity"
                                    label="Memuat"
                                    value={0}
                                    loading
                                />
                            </div>
                        </Section>

                        <Section
                            id="grafik"
                            title="Grafik"
                            text="Recharts lewat ChartContainer, warna hanya dari token --chart-*. BarChartCard (opsional seri di bawah nol, seri pembanding, panel samping), AreaChartCard, DonutCard."
                            code={`<BarChartCard title="Pendapatan per hari" data={[{ x: '2026-11-01', y: 12, n: 4 }]}
    xFormat={formatLocalDate} valueLabel="juta" tooltip={(x, y) => ({ title: String(y), detail: x })} />
<AreaChartCard title="Tren" value={6820000} format="currency" change={-9} data={points} xFormat={formatLocalDate} />
<DonutCard title="Per status" value={20} segments={[{ key: 'paid', label: 'Lunas', value: 8 }]} />`}
                        >
                            <div className="grid gap-6 xl:grid-cols-3">
                                <div className="xl:col-span-2">
                                    <BarChartCard
                                        title="Pendapatan per hari"
                                        data={DAYS.map((x, index) => ({
                                            x,
                                            y: WAVE[index] ?? 0,
                                            n: Math.round(
                                                (WAVE[13 - index] ?? 0) / 2,
                                            ),
                                        }))}
                                        xFormat={formatLocalDate}
                                        tickFormat={(x) =>
                                            `${x.slice(8, 10)}/${x.slice(5, 7)}`
                                        }
                                        valueLabel="juta"
                                        tooltip={(x, y, n) => ({
                                            title: `${y} / −${n ?? 0} juta`,
                                            detail: formatLocalDate(x),
                                        })}
                                    />
                                </div>
                                <div className="flex flex-col gap-6">
                                    <AreaChartCard
                                        title="Tren pendapatan"
                                        value={6820000}
                                        format="currency"
                                        change={-9}
                                        caption="dibanding periode sebelumnya"
                                        data={DAYS.map((x, index) => ({
                                            x,
                                            y: WAVE[index] ?? 0,
                                        }))}
                                        xFormat={formatLocalDate}
                                    />
                                    <DonutCard
                                        title="Invoice per status"
                                        value={20}
                                        caption="invoice dalam rentang"
                                        segments={[
                                            {
                                                key: 'draft',
                                                label: 'Draf',
                                                value: 5,
                                            },
                                            {
                                                key: 'sent',
                                                label: 'Terkirim',
                                                value: 7,
                                            },
                                            {
                                                key: 'paid',
                                                label: 'Lunas',
                                                value: 8,
                                            },
                                        ]}
                                    />
                                </div>
                            </div>
                        </Section>

                        <Section
                            id="grafik-lain"
                            title="Grafik lain"
                            text="ChartCards: LineChartCard, StackedBarChartCard, HorizontalBarChartCard, PieChartCard (inner untuk donat), RadialChartCard, RadarChartCard. Data: baris { label, ...angka per seri } dan seri { key, label }; warna hanya --chart-*."
                            code={`import { LineChartCard } from '@admin-core/components/admin/ChartCards';

<LineChartCard title="Arus barang" rows={[{ label: 'Jan', masuk: 42, keluar: 30 }]}
    series={[{ key: 'masuk', label: 'Masuk' }, { key: 'keluar', label: 'Keluar' }]} />`}
                        >
                            <div className="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
                                <LineChartCard
                                    title="Garis"
                                    subtitle="Masuk dan keluar per bulan"
                                    rows={MONTHS}
                                    series={IN_OUT}
                                />
                                <StackedBarChartCard
                                    title="Batang bertumpuk"
                                    rows={MONTHS}
                                    series={IN_OUT}
                                />
                                <HorizontalBarChartCard
                                    title="Batang horizontal"
                                    rows={SHARE}
                                    series={[
                                        { key: 'nilai', label: 'Persentase' },
                                    ]}
                                />
                                <PieChartCard
                                    title="Pai"
                                    rows={SHARE}
                                    series={[
                                        { key: 'nilai', label: 'Persentase' },
                                    ]}
                                />
                                <RadialChartCard
                                    title="Radial"
                                    rows={SHARE}
                                    series={[
                                        { key: 'nilai', label: 'Persentase' },
                                    ]}
                                />
                                <RadarChartCard
                                    title="Radar"
                                    rows={SKILLS}
                                    series={[
                                        { key: 'tim_a', label: 'Tim A' },
                                        { key: 'tim_b', label: 'Tim B' },
                                    ]}
                                />
                            </div>
                        </Section>

                        <Section
                            id="daftar"
                            title="Daftar"
                            text="ListCard: ikon, judul, subjudul, nilai kanan (tone), ChangePill, dan bar bagian (progress 0–1)."
                            code={`<ListCard title="Pelanggan teratas" empty="Belum ada data." rows={[{
    key: '1', title: 'Toko Sinar Jaya', subtitle: '3 invoice', value: 'Rp 12.500.000',
    icon: 'user-round', tone: 'neutral', href: null, change: 12, progress: 0.8 }]} />`}
                        >
                            <div className="max-w-xl">
                                <ListCard
                                    title="Pelanggan teratas"
                                    empty="Belum ada data."
                                    rows={[
                                        {
                                            key: '1',
                                            title: 'Toko Sinar Jaya',
                                            subtitle: '3 invoice',
                                            value: 'Rp 12.500.000',
                                            icon: 'user-round',
                                            tone: 'success',
                                            href: null,
                                            change: 12,
                                            progress: 1,
                                        },
                                        {
                                            key: '2',
                                            title: 'CV Maju Bersama',
                                            subtitle: '2 invoice',
                                            value: 'Rp 6.300.000',
                                            icon: 'user-round',
                                            tone: 'neutral',
                                            href: null,
                                            change: -4,
                                            progress: 0.5,
                                        },
                                        {
                                            key: '3',
                                            title: 'PT Nusa Abadi',
                                            subtitle: '1 invoice',
                                            value: 'Rp 870.000',
                                            icon: 'user-round',
                                            tone: 'destructive',
                                            href: null,
                                            change: null,
                                            progress: 0.07,
                                        },
                                    ]}
                                />
                            </div>
                        </Section>

                        <Section
                            id="tabel"
                            title="Tabel"
                            text="DataTable (R17): server-side, state di URL, header tebal, baris 56px, fade-up bertahap (data-rows-in). Opsional: expandable (panel rincian), reorderColumns (geser kolom, disimpan per pengguna), onReorder (geser baris), edit per kolom (inline: klik / Enter, Esc batal), stickyHeader, stickyFirstColumn. Kolom: menu Kolom."
                            code={`<DataTable data={invoices} state={table} columns={columns} slug="invoices"
    label="Daftar invoice" itemLabel="invoice" rowLabel={(row) => row.number}
    rowHref={(row) => show(row.id)} empty={{ title: 'Belum ada invoice' }}
    stickyHeader reorderColumns expandable={(row) => <DetailGrid items={...} />}
    onReorder={(ids, done) => router.patch(reorder().url, { ids }, { onSuccess: () => done(true), onError: () => done(false) })} />

// Inline edit of one column:
{ key: 'status', label: 'Status', edit: { type: 'select', options: statuses,
    value: (row) => row.status,
    save: (row, value, done) => router.put(update(row.id).url, { ...row, status: value, lock_version: row.lock_version },
        { onSuccess: () => done(true), onError: () => done(false) }) } }`}
                        >
                            <DataTable
                                data={table}
                                state={state}
                                columns={columns}
                                slug="components"
                                label="Contoh tabel"
                                itemLabel="baris"
                                rowLabel={(row) => row.name}
                                empty={{ title: 'Belum ada data' }}
                                reorderColumns
                                stickyFirstColumn
                                onReorder={(_, done) => done(true)}
                                expandable={(row) => (
                                    <div className="flex flex-col gap-1">
                                        <strong className="text-card-title">
                                            {edited(row).name}
                                        </strong>
                                        <span className="text-muted-foreground">
                                            {row.city} ·{' '}
                                            {formatLocalDate(row.date)} ·{' '}
                                            {formatCurrency(row.total)}
                                        </span>
                                    </div>
                                )}
                            />
                        </Section>

                        <Section
                            id="dialog"
                            title="Dialog"
                            text="ConfirmDialog untuk aksi yang merusak; fokus mulai di Batal. ForceDeleteDialog meminta mengetik nama."
                            code={`<ConfirmDialog open={open} onOpenChange={setOpen} title="Hapus invoice"
    itemName={invoice.number} destructive loading={remove.pending} onConfirm={...} />`}
                        >
                            <Button
                                variant="destructive"
                                onClick={() => setConfirm(true)}
                            >
                                <Trash2 /> Buka dialog hapus
                            </Button>
                            <ConfirmDialog
                                open={confirm}
                                onOpenChange={setConfirm}
                                title="Hapus invoice"
                                itemName="INV-000123"
                                destructive
                                onConfirm={() => setConfirm(false)}
                            />
                        </Section>

                        <Section
                            id="kosong"
                            title="Keadaan kosong"
                            text="EmptyState: ikon, judul, teks, satu aksi. Untuk daftar, tab, dan Beranda."
                            code={`<EmptyState icon={Inbox} title="Belum ada invoice"
    description="Invoice yang dibuat akan muncul di sini." action={<Button>Tambah</Button>} />`}
                        >
                            <EmptyState
                                icon={Inbox}
                                title="Belum ada invoice"
                                description="Invoice yang dibuat akan muncul di sini."
                                action={
                                    <Button>
                                        <Plus /> Tambah
                                    </Button>
                                }
                            />
                        </Section>

                        <Section
                            id="tab"
                            title="Tab"
                            text="Tabs untuk bagian halaman detail (Detail, Lampiran, Riwayat); panah kiri/kanan berpindah tab."
                            code={`<Tabs label="Bagian invoice" value={tab} onChange={setTab}
    tabs={[{ key: 'detail', label: 'Detail', content: <DetailGrid ... /> }]} />`}
                        >
                            <Tabs
                                label="Contoh tab"
                                value={tab}
                                onChange={setTab}
                                tabs={[
                                    {
                                        key: 'detail',
                                        label: 'Detail',
                                        content: (
                                            <p className="m-0 pt-4 text-muted-foreground">
                                                Isi tab Detail.
                                            </p>
                                        ),
                                    },
                                    {
                                        key: 'lampiran',
                                        label: 'Lampiran',
                                        content: (
                                            <p className="m-0 pt-4 text-muted-foreground">
                                                Isi tab Lampiran.
                                            </p>
                                        ),
                                    },
                                ]}
                            />
                        </Section>

                        <Section
                            id="kanban"
                            title="Kanban"
                            text="KanbanBoard: kolom per status, kartu digeser (mouse), dengan keyboard di dalam kolom (Space, panah, Space), atau lewat menu Pindahkan ke (keyboard dan satu klik). onMove mengirim urutan baru ke rute PATCH modul. Contoh nyata: Kanban → Papan."
                            code={`<KanbanBoard label="Papan tugas" columns={board.columns} items={cards}
    cardLabel={(card) => card.title} renderCard={(card) => <TaskCard card={card} />}
    onMove={(columns, done) => router.patch(moveHref, { columns },
        { onSuccess: () => done(true), onError: () => done(false) })} />`}
                        >
                            <KanbanBoard
                                label="Contoh papan"
                                columns={[
                                    {
                                        key: 'rencana',
                                        label: 'Rencana',
                                        ids: [1, 2],
                                    },
                                    {
                                        key: 'dikerjakan',
                                        label: 'Dikerjakan',
                                        ids: [3],
                                    },
                                    {
                                        key: 'selesai',
                                        label: 'Selesai',
                                        ids: [],
                                    },
                                ]}
                                items={{
                                    1: 'Rapikan arsip',
                                    2: 'Siapkan laporan',
                                    3: 'Periksa invoice',
                                }}
                                cardLabel={(item) => item}
                                renderCard={(item) => (
                                    <span className="font-medium">{item}</span>
                                )}
                                onMove={(_, done) => done(true)}
                            />
                        </Section>

                        <Section
                            id="menu"
                            title="Menu"
                            text="Menu datang dari module.php, tidak pernah ditulis di TSX. 'badge' memberi pill di item; 'submenu' membuat item induk dengan anak (lihat Invoice di sidebar)."
                            code={`// modules/Invoice/module.php
'badge' => 'Baru',
'submenu' => [
    ['label' => 'Daftar invoice', 'route' => 'index'],
    ['label' => 'Buat invoice', 'route' => 'create', 'action' => 'create', 'badge' => 'Baru'],
],`}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

/** One catalogue entry: title, when to use it, the live example, and the code to copy. */
function Section({
    id,
    title,
    text,
    code,
    children,
}: {
    id: string;
    title: string;
    text: string;
    code: string;
    children?: ReactNode;
}) {
    const [copied, setCopied] = useState(false);

    return (
        <section
            id={id}
            aria-labelledby={`${id}-title`}
            className="flex scroll-mt-6 flex-col gap-4"
        >
            <div className="flex flex-col gap-1">
                <h2 id={`${id}-title`} className="m-0 text-section">
                    {title}
                </h2>
                <p className="m-0 max-w-3xl text-body text-muted-foreground">
                    {text}
                </p>
            </div>
            {children && (
                <div className="rounded-xl border border-border bg-card p-6">
                    {children}
                </div>
            )}
            <div className="relative rounded-xl border border-border bg-muted">
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={copied ? 'Tersalin' : 'Salin kode'}
                    className="absolute top-2 right-2"
                    onClick={() =>
                        navigator.clipboard.writeText(code).then(() => {
                            setCopied(true);
                            window.setTimeout(() => setCopied(false), 1500);
                        })
                    }
                >
                    {copied ? <Check /> : <Copy />}
                </Button>
                <pre className="m-0 overflow-x-auto p-4 pr-14 font-mono text-table">
                    <code>{code}</code>
                </pre>
            </div>
        </section>
    );
}
