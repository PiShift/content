import { Link } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Button } from '../components/ui/Button';
import { CellValue } from '../components/ui/CellValue';
import { Icon } from '../components/ui/Icon';
import { AdminLayout } from '../layouts/AdminLayout';
import type { Cell, Tone } from '../lib/types';
import { cn, useT } from '../lib/utils';

interface StatItem {
    label: string;
    value: number | string;
    description?: string | null;
    icon?: string;
    tone?: Tone;
    url?: string | null;
}

interface Series {
    label: string;
    data: number[];
}

type Widget = { key: string; width: 'full' | 'half' } & (
    | { type: 'stats'; items: StatItem[] }
    | { type: 'chart'; title: string; labels: string[]; series: Series[] }
    | { type: 'table'; title: string; columns: Record<string, string>; rows: Array<{ cells: Record<string, Cell>; url?: string | null }>; url?: string | null }
);

const TONE_ICON: Record<Tone, string> = {
    neutral: 'bg-sunken text-ink-soft',
    info: 'bg-info-soft text-info',
    success: 'bg-accent-soft text-accent-strong',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
};

/** Categorical slots in fixed order (validated reference palette, light surface). */
const SERIES_COLORS = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4'];

export default function Dashboard({ widgets, urls }: { widgets: Widget[]; urls: { pages: string | null } }) {
    const t = useT();

    return (
        <AdminLayout title={t('dashboard')}>
            {widgets.length === 0 ? (
                <div className="rounded-panel border border-line bg-panel p-8 text-center shadow-panel">
                    <h2 className="text-lg font-semibold">{t('welcome')}</h2>
                    <p className="mt-1 text-sm text-ink-soft">{t('welcome_text')}</p>
                    {urls.pages && (
                        <Link href={urls.pages} className="mt-5 inline-block">
                            <Button variant="primary" icon="pages" tabIndex={-1}>
                                {t('go_to_pages')}
                            </Button>
                        </Link>
                    )}
                </div>
            ) : (
                <div className="grid gap-5 lg:grid-cols-2">
                    {widgets.map((widget) => (
                        <div key={widget.key} className={cn('min-w-0', widget.width === 'full' && 'lg:col-span-2')}>
                            {widget.type === 'stats' && <Stats items={widget.items} />}
                            {widget.type === 'chart' && <LineChart title={widget.title} labels={widget.labels} series={widget.series} />}
                            {widget.type === 'table' && <Table {...widget} />}
                        </div>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}

function Stats({ items }: { items: StatItem[] }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
            {items.map((item) => {
                const body = (
                    <div className="flex h-full items-start gap-4 rounded-panel border border-line bg-panel p-5 shadow-panel transition-colors hover:border-line-strong">
                        {item.icon && (
                            <span className={cn('grid size-10 shrink-0 place-items-center rounded-control', TONE_ICON[item.tone ?? 'neutral'])}>
                                <Icon name={item.icon} size={20} />
                            </span>
                        )}
                        <div className="min-w-0">
                            <p className="text-sm text-ink-soft">{item.label}</p>
                            <p className="mt-0.5 text-2xl font-bold tabular-nums">{item.value}</p>
                            {item.description && <p className="mt-1 text-xs text-ink-faint">{item.description}</p>}
                        </div>
                    </div>
                );

                return item.url ? (
                    <Link key={item.label} href={item.url} className="block">
                        {body}
                    </Link>
                ) : (
                    <div key={item.label}>{body}</div>
                );
            })}
        </div>
    );
}

function Panel({ title, action, children }: { title: string; action?: ReactNode; children: ReactNode }) {
    return (
        <section className="h-full rounded-panel border border-line bg-panel shadow-panel">
            <header className="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                <h2 className="text-[15px] font-semibold">{title}</h2>
                {action}
            </header>
            {children}
        </section>
    );
}

/**
 * Small multi-series line chart: one y-axis, recessive grid, legend, hover tooltip per
 * label, and a screen-reader table with the same numbers.
 */
function LineChart({ title, labels, series }: { title: string; labels: string[]; series: Series[] }) {
    const t = useT();
    const [hover, setHover] = useState<number | null>(null);
    const width = 640;
    const height = 220;
    const pad = { top: 12, right: 16, bottom: 28, left: 32 };
    const max = Math.max(1, ...series.flatMap((item) => item.data));
    const step = niceStep(max);
    const top = Math.ceil(max / step) * step;
    const ticks = Array.from({ length: Math.round(top / step) + 1 }, (_, index) => index * step);
    const x = (index: number) => pad.left + (labels.length <= 1 ? 0 : (index * (width - pad.left - pad.right)) / (labels.length - 1));
    const y = (value: number) => height - pad.bottom - (value / top) * (height - pad.top - pad.bottom);
    const empty = series.every((item) => item.data.every((value) => value === 0));

    return (
        <Panel title={title}>
            <div className="p-5">
                {series.length > 1 && (
                    <ul className="mb-3 flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-ink-soft">
                        {series.map((item, index) => (
                            <li key={item.label} className="inline-flex items-center gap-2">
                                <span className="h-0.5 w-4 rounded-full" style={{ background: SERIES_COLORS[index % SERIES_COLORS.length] }} />
                                {item.label}
                            </li>
                        ))}
                    </ul>
                )}

                <div className="relative" dir="ltr">
                    <svg viewBox={`0 0 ${width} ${height}`} className="h-auto w-full" role="img" aria-label={title} onMouseLeave={() => setHover(null)}>
                        {ticks.map((tick) => (
                            <g key={tick}>
                                <line x1={pad.left} x2={width - pad.right} y1={y(tick)} y2={y(tick)} stroke="var(--color-line)" strokeWidth={1} />
                                <text x={pad.left - 8} y={y(tick)} dy="0.32em" textAnchor="end" fontSize={11} fill="var(--color-ink-faint)">
                                    {tick}
                                </text>
                            </g>
                        ))}
                        {labels.map((label, index) => (
                            <text key={label} x={x(index)} y={height - 8} textAnchor="middle" fontSize={11} fill="var(--color-ink-faint)">
                                {label}
                            </text>
                        ))}
                        {hover !== null && <line x1={x(hover)} x2={x(hover)} y1={pad.top} y2={height - pad.bottom} stroke="var(--color-line-strong)" strokeWidth={1} />}
                        {series.map((item, seriesIndex) => {
                            const color = SERIES_COLORS[seriesIndex % SERIES_COLORS.length];

                            return (
                                <g key={item.label}>
                                    <polyline
                                        points={item.data.map((value, index) => `${x(index)},${y(value)}`).join(' ')}
                                        fill="none"
                                        stroke={color}
                                        strokeWidth={2}
                                        strokeLinejoin="round"
                                        strokeLinecap="round"
                                    />
                                    {hover !== null && <circle cx={x(hover)} cy={y(item.data[hover] ?? 0)} r={4} fill={color} stroke="var(--color-panel)" strokeWidth={2} />}
                                </g>
                            );
                        })}
                        {labels.map((label, index) => (
                            <rect
                                key={label}
                                x={x(index) - (width - pad.left - pad.right) / Math.max(1, labels.length - 1) / 2}
                                y={0}
                                width={(width - pad.left - pad.right) / Math.max(1, labels.length - 1)}
                                height={height}
                                fill="transparent"
                                onMouseEnter={() => setHover(index)}
                            />
                        ))}
                    </svg>

                    {hover !== null && (
                        <div
                            className="pointer-events-none absolute top-2 z-10 min-w-36 rounded-control border border-line bg-panel px-3 py-2 text-[13px] shadow-pop"
                            style={x(hover) > width / 2 ? { right: `${((width - x(hover)) / width) * 100 + 2}%` } : { left: `${(x(hover) / width) * 100 + 2}%` }}
                        >
                            <p className="mb-1 font-semibold">{labels[hover]}</p>
                            {series.map((item, index) => (
                                <p key={item.label} className="flex items-center justify-between gap-4 text-ink-soft">
                                    <span className="inline-flex items-center gap-1.5">
                                        <span className="size-2 rounded-full" style={{ background: SERIES_COLORS[index % SERIES_COLORS.length] }} />
                                        {item.label}
                                    </span>
                                    <span className="font-semibold text-ink tabular-nums">{item.data[hover] ?? 0}</span>
                                </p>
                            ))}
                        </div>
                    )}
                </div>

                {empty && <p className="mt-2 text-center text-[13px] text-ink-faint">{t('no_data')}</p>}

                <table className="sr-only">
                    <caption>{title}</caption>
                    <thead>
                        <tr>
                            <th />
                            {series.map((item) => (
                                <th key={item.label}>{item.label}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {labels.map((label, index) => (
                            <tr key={label}>
                                <th>{label}</th>
                                {series.map((item) => (
                                    <td key={item.label}>{item.data[index] ?? 0}</td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Panel>
    );
}

function niceStep(max: number): number {
    const rough = max / 4;
    const power = 10 ** Math.floor(Math.log10(Math.max(rough, 1)));

    return [1, 2, 5, 10].map((factor) => factor * power).find((candidate) => candidate >= rough) ?? power * 10;
}

function Table({ title, columns, rows, url }: { title: string; columns: Record<string, string>; rows: Array<{ cells: Record<string, Cell>; url?: string | null }>; url?: string | null }) {
    const t = useT();
    const entries = Object.entries(columns);

    return (
        <Panel
            title={title}
            action={
                url && (
                    <Link href={url} className="text-[13px] font-medium text-accent hover:text-accent-strong">
                        {t('view_all')}
                    </Link>
                )
            }
        >
            {rows.length === 0 ? (
                <p className="py-10 text-center text-sm text-ink-faint">{t('no_records')}</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-canvas text-xs font-semibold text-ink-faint">
                            <tr>
                                {entries.map(([key, label]) => (
                                    <th key={key} className="px-4 py-2.5 text-start font-semibold whitespace-nowrap">
                                        {label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {rows.map((row, index) => (
                                <tr key={index} className={cn(row.url && 'hover:bg-canvas')}>
                                    {entries.map(([key], cellIndex) => (
                                        <td key={key} className={cn('px-4 py-2.5', cellIndex === 0 && 'font-medium')}>
                                            {row.url && cellIndex === 0 ? (
                                                <Link href={row.url} className="hover:underline">
                                                    <CellValue cell={row.cells[key]} />
                                                </Link>
                                            ) : (
                                                <CellValue cell={row.cells[key]} />
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </Panel>
    );
}
