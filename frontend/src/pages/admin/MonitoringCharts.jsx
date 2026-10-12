import { Bar, BarChart, CartesianGrid, Cell, LabelList, Line, LineChart, Tooltip, XAxis, YAxis } from 'recharts';
import ChartCard, { chartColor } from '../../components/ChartCard';
import { formatCount, formatMoney, formatShortDate, tierNameFromCode } from '../../utils/format';

// The four monitoring charts (design/components/ChartCard, FR-A-08 to FR-A-11).
// Each card says its takeaway in words, so the meaning is not in the colours only.

const axisTick = () => ({ fill: chartColor('ink-muted'), fontSize: 12 });

// Motion stops for people who ask the system for reduced motion (design README "States and motion").
const shouldAnimate = () => !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function VisitorsPerDayChart({ data }) {
  const busiest = [...data].sort((first, second) => second.visitors - first.visitors)[0];
  const takeaway = busiest ? `Busiest day: ${formatShortDate(busiest.date)} with ${formatCount(busiest.visitors)} visitors` : 'No check-ins yet';

  return (
    <ChartCard title="Visitors per day" takeaway={takeaway} className="is-wide">
      <LineChart responsive style={{ width: '100%', height: 240 }} data={data} margin={{ top: 8, right: 16, bottom: 0, left: 0 }}>
        <CartesianGrid vertical={false} stroke={chartColor('chart-grid')} />
        <XAxis dataKey="date" tickFormatter={formatShortDate} tick={axisTick()} axisLine={false} tickLine={false} minTickGap={24} />
        <YAxis tick={axisTick()} axisLine={false} tickLine={false} width={48} />
        <Tooltip labelFormatter={formatShortDate} formatter={(value) => [formatCount(value), 'Visitors']} />
        <Line isAnimationActive={shouldAnimate()} type="monotone" dataKey="visitors" name="Visitors" stroke={chartColor('chart-1')} strokeWidth={2.5} dot={false} activeDot={{ r: 5 }} />
      </LineChart>
    </ChartCard>
  );
}

export function TicketsByTierChart({ data }) {
  const chartData = data.map((row) => ({ ...row, tier: tierNameFromCode(row.tier_code) }));
  const topTier = chartData[0];
  const takeaway = topTier ? `${topTier.tier} brings the most revenue (${formatMoney(topTier.revenue, 'USD', { decimals: 0 })})` : 'No tickets sold yet';

  return (
    <ChartCard title="Tickets and revenue by tier" takeaway={takeaway} legend={[{ label: 'Tickets', color: 'chart-1' }, { label: 'Revenue (USD)', color: 'chart-2' }]}>
      <BarChart responsive style={{ width: '100%', height: 240 }} data={chartData} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
        <CartesianGrid vertical={false} stroke={chartColor('chart-grid')} />
        <XAxis dataKey="tier" tick={axisTick()} axisLine={false} tickLine={false} />
        <YAxis yAxisId="tickets" tick={axisTick()} axisLine={false} tickLine={false} width={40} />
        <YAxis yAxisId="revenue" orientation="right" tick={axisTick()} axisLine={false} tickLine={false} width={64} tickFormatter={(value) => formatCount(value)} />
        <Tooltip formatter={(value, name) => (name === 'Revenue (USD)' ? formatMoney(value, 'USD', { decimals: 0 }) : formatCount(value))} />
        <Bar isAnimationActive={shouldAnimate()} yAxisId="tickets" dataKey="tickets" name="Tickets" fill={chartColor('chart-1')} radius={[4, 4, 0, 0]} />
        <Bar isAnimationActive={shouldAnimate()} yAxisId="revenue" dataKey="revenue" name="Revenue (USD)" fill={chartColor('chart-2')} radius={[4, 4, 0, 0]} />
      </BarChart>
    </ChartCard>
  );
}

export function TopRidesChart({ data }) {
  const topEight = data.slice(0, 8);
  const takeaway = topEight[0] ? `Most popular: ${topEight[0].attraction_name}` : 'No rides used yet';

  return (
    <ChartCard title="Most popular rides" takeaway={takeaway}>
      <BarChart responsive style={{ width: '100%', height: Math.max(240, topEight.length * 34) }} data={topEight} layout="vertical" margin={{ top: 0, right: 48, bottom: 0, left: 0 }}>
        <CartesianGrid horizontal={false} stroke={chartColor('chart-grid')} />
        <XAxis type="number" tick={axisTick()} axisLine={false} tickLine={false} />
        <YAxis type="category" dataKey="attraction_name" tick={axisTick()} axisLine={false} tickLine={false} width={120} />
        <Tooltip formatter={(value) => [formatCount(value), 'Rides']} />
        <Bar isAnimationActive={shouldAnimate()} dataKey="uses" name="Rides" fill={chartColor('chart-1')} radius={[0, 4, 4, 0]}>
          <LabelList dataKey="uses" position="right" formatter={formatCount} style={{ fill: chartColor('ink-muted'), fontSize: 12 }} />
        </Bar>
      </BarChart>
    </ChartCard>
  );
}

// Hours 09 to 20 always shown. The three busiest hours are in coral and named in the takeaway.
export function ActivityByHourChart({ data }) {
  const byHour = Object.fromEntries(data.map((row) => [row.hour, row]));
  const hours = Array.from({ length: 12 }, (_, index) => index + 9).map((hour) => ({
    hour,
    label: `${String(hour).padStart(2, '0')}:00`,
    total: (byHour[hour]?.checkins || 0) + (byHour[hour]?.ride_uses || 0),
  }));
  const peakHours = [...hours].filter((row) => row.total > 0).sort((first, second) => second.total - first.total).slice(0, 3).map((row) => row.hour);
  const quietest = [...hours].sort((first, second) => first.total - second.total)[0];
  const takeaway = peakHours.length
    ? `Busiest ${peakHours.sort((a, b) => a - b).map((hour) => `${String(hour).padStart(2, '0')}:00`).join(', ')} · quietest ${quietest.label}`
    : 'No activity yet';

  return (
    <ChartCard title="Activity by hour of day" takeaway={takeaway} className="is-wide" legend={[{ label: 'Peak hours', color: 'chart-3' }, { label: 'Other hours', color: 'chart-1' }]}>
      <BarChart responsive style={{ width: '100%', height: 240 }} data={hours} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
        <CartesianGrid vertical={false} stroke={chartColor('chart-grid')} />
        <XAxis dataKey="label" tick={axisTick()} axisLine={false} tickLine={false} />
        <YAxis tick={axisTick()} axisLine={false} tickLine={false} width={48} />
        <Tooltip formatter={(value) => [formatCount(value), 'Check-ins + ride uses']} />
        <Bar isAnimationActive={shouldAnimate()} dataKey="total" name="Check-ins + ride uses" radius={[4, 4, 0, 0]}>
          {hours.map((row) => <Cell key={row.hour} fill={chartColor(peakHours.includes(row.hour) ? 'chart-3' : 'chart-1')} />)}
        </Bar>
      </BarChart>
    </ChartCard>
  );
}
