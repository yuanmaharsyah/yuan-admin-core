import type { ReactNode } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Line,
    LineChart,
    Pie,
    PieChart,
    PolarAngleAxis,
    PolarGrid,
    Radar,
    RadarChart,
    RadialBar,
    RadialBarChart,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatNumber } from '../../lib/format';
import { cn } from '../../lib/utils';
import { ChartContainer, useReducedMotion } from '../ui/chart';
import { CardHeading } from './CardHeading';

/**
 * The chart list (restyle 03, Komponen → Grafik): line, stacked bar,
 * horizontal bar, pie, radial and radar cards on recharts. Colours come only
 * from the `--chart-*` tokens (series in order); each card also gives screen
 * readers its values as a hidden table (ChartContainer).
 */

/** One row: `label` on the axis, then a number per series key. */
export type ChartRow = { label: string } & Record<string, string | number>;
export type ChartSeries = { key: string; label: string };

/** Series colours through `color` (lines and radars draw with `currentColor`). */
const TEXTS = [
    'text-chart-1',
    'text-chart-2',
    'text-chart-3',
    'text-chart-4',
    'text-chart-5',
];
const FILLS = [
    'fill-chart-1',
    'fill-chart-2',
    'fill-chart-3',
    'fill-chart-4',
    'fill-chart-5',
];
const DOTS = [
    'bg-chart-1',
    'bg-chart-2',
    'bg-chart-3',
    'bg-chart-4',
    'bg-chart-5',
];

type Props = {
    title: string;
    subtitle?: string;
    rows: ChartRow[];
    series: ChartSeries[];
    action?: ReactNode;
};

const value = (row: ChartRow, key: string) => Number(row[key] ?? 0);

/** The card frame, the legend and the hidden table every chart card shares. */
function Frame({
    title,
    subtitle,
    rows,
    series,
    action,
    children,
    className,
}: Props & { children: React.ReactElement; className?: string }) {
    return (
        <section
            aria-label={title}
            className="flex min-w-0 flex-col gap-4 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <CardHeading title={title} subtitle={subtitle} action={action} />
            <ChartContainer
                title={title}
                headers={[
                    title,
                    series.map((serie) => serie.label).join(' / '),
                ]}
                rows={rows.map((row) => [
                    row.label,
                    series
                        .map((serie) => formatNumber(value(row, serie.key)))
                        .join(' / '),
                ])}
                className={cn('h-64', className)}
            >
                {children}
            </ChartContainer>
            <ul className="m-0 flex list-none flex-wrap justify-center gap-x-5 gap-y-2 p-0 text-table text-muted-foreground">
                {series.map((serie, index) => (
                    <li key={serie.key} className="flex items-center gap-2">
                        <span
                            aria-hidden="true"
                            className={cn(
                                'size-2 rounded-full',
                                DOTS[index % DOTS.length],
                            )}
                        />
                        {serie.label}
                    </li>
                ))}
            </ul>
        </section>
    );
}

function TooltipBox({
    active,
    label,
    payload,
}: {
    active?: boolean;
    label?: string | number;
    payload?: readonly { name?: string | number; value?: unknown }[];
}) {
    return active && payload && payload.length > 0 ? (
        <div className="flex flex-col gap-0.5 rounded-lg border border-border bg-popover px-3 py-2 text-popover-foreground shadow-popover">
            {label !== undefined && (
                <span className="text-xs text-muted-foreground">{label}</span>
            )}
            {payload.map((item) => (
                <span
                    key={String(item.name)}
                    className="text-table tabular-nums"
                >
                    {item.name}:{' '}
                    <strong className="font-semibold">
                        {formatNumber(Number(item.value ?? 0))}
                    </strong>
                </span>
            ))}
        </div>
    ) : null;
}

const axis = 'text-xs [&_text]:fill-muted-foreground';

export function LineChartCard(props: Props) {
    const reduced = useReducedMotion();

    return (
        <Frame {...props}>
            <LineChart
                data={props.rows}
                margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
            >
                <CartesianGrid vertical={false} className="stroke-border" />
                <XAxis
                    dataKey="label"
                    tickLine={false}
                    axisLine={false}
                    className={axis}
                />
                <YAxis
                    width={36}
                    tickLine={false}
                    axisLine={false}
                    className={axis}
                />
                <Tooltip
                    cursor={false}
                    content={(tip) => <TooltipBox {...tip} />}
                />
                {props.series.map((serie, index) => (
                    <Line
                        key={serie.key}
                        dataKey={serie.key}
                        name={serie.label}
                        type="monotone"
                        strokeWidth={2}
                        dot={false}
                        stroke="currentColor"
                        className={TEXTS[index % TEXTS.length]}
                        isAnimationActive={!reduced}
                    />
                ))}
            </LineChart>
        </Frame>
    );
}

export function StackedBarChartCard(props: Props) {
    const reduced = useReducedMotion();

    return (
        <Frame {...props}>
            <BarChart
                data={props.rows}
                margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
            >
                <CartesianGrid vertical={false} className="stroke-border" />
                <XAxis
                    dataKey="label"
                    tickLine={false}
                    axisLine={false}
                    className={axis}
                />
                <YAxis
                    width={36}
                    tickLine={false}
                    axisLine={false}
                    className={axis}
                />
                <Tooltip
                    cursor={false}
                    content={(tip) => <TooltipBox {...tip} />}
                />
                {props.series.map((serie, index) => (
                    <Bar
                        key={serie.key}
                        dataKey={serie.key}
                        name={serie.label}
                        stackId="stack"
                        maxBarSize={28}
                        className={FILLS[index % FILLS.length]}
                        radius={
                            index === props.series.length - 1 ? [6, 6, 0, 0] : 0
                        }
                        isAnimationActive={!reduced}
                    />
                ))}
            </BarChart>
        </Frame>
    );
}

export function HorizontalBarChartCard(props: Props) {
    const reduced = useReducedMotion();

    return (
        <Frame {...props}>
            <BarChart
                layout="vertical"
                data={props.rows}
                margin={{ top: 0, right: 8, bottom: 0, left: 0 }}
            >
                <CartesianGrid horizontal={false} className="stroke-border" />
                <XAxis
                    type="number"
                    tickLine={false}
                    axisLine={false}
                    className={axis}
                />
                <YAxis
                    type="category"
                    dataKey="label"
                    width={80}
                    tickLine={false}
                    axisLine={false}
                    className={axis}
                />
                <Tooltip
                    cursor={false}
                    content={(tip) => <TooltipBox {...tip} />}
                />
                {props.series.map((serie, index) => (
                    <Bar
                        key={serie.key}
                        dataKey={serie.key}
                        name={serie.label}
                        maxBarSize={14}
                        radius={[0, 6, 6, 0]}
                        className={FILLS[(index + 1) % FILLS.length]}
                        isAnimationActive={!reduced}
                    />
                ))}
            </BarChart>
        </Frame>
    );
}

/** Pie of the first series per row (a donut when `inner`). */
export function PieChartCard({
    inner = false,
    ...props
}: Props & { inner?: boolean }) {
    const reduced = useReducedMotion();
    const key = props.series[0]?.key ?? 'value';

    return (
        <Frame
            {...props}
            series={props.rows.map((row) => ({
                key: row.label,
                label: row.label,
            }))}
        >
            <PieChart>
                <Tooltip content={(tip) => <TooltipBox {...tip} />} />
                <Pie
                    data={props.rows}
                    dataKey={key}
                    nameKey="label"
                    innerRadius={inner ? '60%' : 0}
                    outerRadius="90%"
                    paddingAngle={2}
                    cornerRadius={4}
                    stroke="none"
                    isAnimationActive={!reduced}
                >
                    {props.rows.map((row, index) => (
                        <Cell
                            key={row.label}
                            className={FILLS[index % FILLS.length]}
                        />
                    ))}
                </Pie>
            </PieChart>
        </Frame>
    );
}

/** Radial bars of the first series per row, e.g. progress per target. */
export function RadialChartCard(props: Props) {
    const reduced = useReducedMotion();
    const key = props.series[0]?.key ?? 'value';

    return (
        <Frame
            {...props}
            series={props.rows.map((row) => ({
                key: row.label,
                label: row.label,
            }))}
        >
            <RadialBarChart
                data={props.rows}
                innerRadius="30%"
                outerRadius="100%"
                startAngle={90}
                endAngle={-270}
            >
                <Tooltip content={(tip) => <TooltipBox {...tip} />} />
                <RadialBar
                    dataKey={key}
                    name={props.series[0]?.label}
                    background={{ className: 'fill-muted' }}
                    cornerRadius={6}
                    isAnimationActive={!reduced}
                >
                    {props.rows.map((row, index) => (
                        <Cell
                            key={row.label}
                            className={FILLS[index % FILLS.length]}
                        />
                    ))}
                </RadialBar>
            </RadialBarChart>
        </Frame>
    );
}

export function RadarChartCard(props: Props) {
    const reduced = useReducedMotion();

    return (
        <Frame {...props}>
            <RadarChart data={props.rows} outerRadius="75%">
                <PolarGrid className="stroke-border" />
                <PolarAngleAxis dataKey="label" className={axis} />
                <Tooltip content={(tip) => <TooltipBox {...tip} />} />
                {props.series.map((serie, index) => (
                    <Radar
                        key={serie.key}
                        dataKey={serie.key}
                        name={serie.label}
                        stroke="currentColor"
                        fill="currentColor"
                        fillOpacity={0.15}
                        className={TEXTS[index % TEXTS.length]}
                        isAnimationActive={!reduced}
                    />
                ))}
            </RadarChart>
        </Frame>
    );
}
