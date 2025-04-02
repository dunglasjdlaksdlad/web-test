import { useEffect, useState } from "react";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ReferenceLine,
  ResponsiveContainer,
  Sector,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Button } from "./ui/button";
import DataTableBar from "./data-table-bar";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import React from "react";

const COLORS = [
  '#00C49F', '#0088FE', '#FFBB28', '#FF8042',
  '#FF6384', '#E74C3C', '#9966FF', '#82CA9D',
  "#4682B4", "#FFD700", "#A52A2A", "#8B4513",
];

const renderCustomShape = (props: any) => {
  const RADIAN = Math.PI / 180;
  const { cx, cy, midAngle, innerRadius, outerRadius, startAngle, endAngle, fill, payload, percent, value } = props;
  const sin = Math.sin(-RADIAN * midAngle);
  const cos = Math.cos(-RADIAN * midAngle);
  const sx = cx + (outerRadius + 10) * cos;
  const sy = cy + (outerRadius + 10) * sin;
  const mx = cx + (outerRadius + 30) * cos;
  const my = cy + (outerRadius + 30) * sin;
  const ex = mx + (cos >= 0 ? 1 : -1) * 22;
  const ey = my;
  const textAnchor = cos >= 0 ? "start" : "end";

  return (
    <g>
      <Sector
        cx={cx}
        cy={cy}
        innerRadius={innerRadius}
        outerRadius={outerRadius}
        startAngle={startAngle}
        endAngle={endAngle}
        fill={fill}
        stroke="#fff"
        strokeWidth={2}
      />
      <Sector
        cx={cx}
        cy={cy}
        startAngle={startAngle}
        endAngle={endAngle}
        innerRadius={outerRadius + 4}
        outerRadius={outerRadius + 8}
        fill={fill}
      />
      <path d={`M${sx},${sy}L${mx},${my}L${ex},${ey}`} stroke={fill} fill="none" />
      <circle cx={ex} cy={ey} r={3} fill={fill} stroke="none" />
      <text x={ex + (cos >= 0 ? 4 : -4)} y={ey} textAnchor={textAnchor} fill="#333" fontSize={12}>
        {`${payload.name}: ${value}`}
      </text>
      <text x={ex + (cos >= 0 ? 4 : -4)} y={ey} dy={16} textAnchor={textAnchor} fill="#999" fontSize={10}>
        {`(${(percent * 100).toFixed(2)}%)`}
      </text>
    </g>
  );
};

const renderCustomTooltip = ({ active, payload }: any) => {
  if (active && payload?.length) {
    return (
      <div className="bg-white shadow-lg rounded-md p-2 border border-gray-200 text-sm">
        <p className="text-center font-semibold text-gray-700 pb-2">{payload[0].payload.ttkv}</p>
        <p className="font-semibold text-gray-700">{payload[0].payload.name}</p>
        {payload.map((entry: any, index: number) => (
          <div
            key={index}
            className="flex justify-between text-gray-600"
            style={{ minWidth: "150px" }}
          >
            <span style={{ color: COLORS[index % COLORS.length] }}>{entry.name}:</span>
            <span className="font-semibold">{entry.value}</span>
          </div>
        ))}
      </div>
    );
  }
  return null;
};

// Pie Chart Component (bỏ React.memo)
const PieChartComponent = ({ chartData, title }: { chartData: any; title: string }) => {
  return (
    <Card className="flex flex-col container">
      <CardHeader className="items-center pb-0 pt-4">
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="p-0 flex-1" style={{ height: 325 }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <defs>
              {chartData?.pie?.map((_: any, index: number) => (
                <linearGradient key={index} id={`pieColor${index}`} x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.9} />
                  <stop offset="100%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.5} />
                </linearGradient>
              ))}
            </defs>
            <Pie
              data={chartData?.pie || []}
              cx="50%"
              cy="60%"
              innerRadius="25%"
              outerRadius="45%"
              dataKey="value"
              labelLine={false}
              label={renderCustomShape}
              stroke="#fff"
              strokeWidth={2}
            >
              {chartData?.pie?.map((_: any, index: number) => (
                <Cell key={`cell-${index}`} fill={`url(#pieColor${index})`} />
              ))}
            </Pie>
            <Legend
              layout="horizontal"
              align="center"
              verticalAlign="top"
              iconSize={10}
              wrapperStyle={{ fontSize: "12px", marginTop: "-10px" }}
            />
          </PieChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  );
};

// Bar Chart Component (bỏ React.memo)
const BarChartComponent = ({
  chartData,
  title,
  keyTitle,
  isExpanded,
  toggleExpanded,
}: {
  chartData: any;
  title: string;
  keyTitle: string;
  isExpanded: boolean;
  toggleExpanded: () => void;
}) => {
  const barKeys = Object.keys(chartData?.barDataTable?.[0] || {}).filter((key) => key !== "ttkv");
  console.log('123', chartData);
  barKeys.map((key, index) => (
    console.log(["WO QH > 5 ngày"].includes(key) ? "left" : "right")
  ));

  return (
    <Card className="md:col-span-3 container">
      <CardHeader className="items-center pb-0 pt-4">
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <div className="flex flex-col">
        <CardContent className="p-0" style={{ height: 325 }}>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={chartData?.barDataTable || []}
              margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
            >
              <defs>
                {barKeys.map((_, index) => (
                  <linearGradient key={index} id={`color${index}`} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.9} />
                    <stop offset="100%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.5} />
                  </linearGradient>
                ))}
              </defs>
              <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.5} vertical={false} />
              <XAxis dataKey="ttkv" />
              <YAxis yAxisId="left" label={{ value: "Số lượng", position: 'top', dy: -20 }} />
              <YAxis yAxisId="right" orientation="right" label={{ value: "Giá trị (VNĐ)", position: 'top', dy: -20 }} />
              <Tooltip content={renderCustomTooltip} />
              <Legend
                layout="horizontal"
                align="center"
                verticalAlign="top"
                iconSize={10}
                wrapperStyle={{ fontSize: "12px", marginTop: "-10px" }}
              />
              <ReferenceLine y={10} yAxisId="left" stroke="red" />
              {barKeys.map((key, index) => (
                <Bar
                  key={key}
                  dataKey={key}
                  fill={`url(#color${index})`}
                  radius={[8, 8, 0, 0]}
                  barSize={30}
                  yAxisId={key === 'WO QH > 5 ngày' ? "left" : "right"}
                />
              ))}
            </BarChart>
          </ResponsiveContainer>
        </CardContent>
        <CardFooter className="flex flex-col items-center">
          <Button
            onClick={toggleExpanded}
            variant="link"
            className="text-amber-500 hover:underline mt-2"
          >
            {isExpanded ? "Read Less" : "Read More"}
          </Button>
        </CardFooter>
      </div>
    </Card>
  );
};

// DataTableBar (bỏ React.memo)
const DataTableBarComponent = DataTableBar;

const ChartDashboard = ({ data }: { data: any }) => {
  const [expandedStates, setExpandedStates] = useState<{ [key: string]: boolean }>({});
  const [chartData, setChartData] = useState(data);

  useEffect(() => {
    setChartData(data);
    const initialExpandedStates: { [key: string]: boolean } = {};
    Object.keys(data).forEach((key) => {
      initialExpandedStates[key] = false;
    });
    setExpandedStates(initialExpandedStates);
  }, [data]);

  const toggleExpanded = (key: string) => {
    setExpandedStates((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  return (
    <div className="flex flex-col gap-4">
      {Object.keys(chartData).map((key) => (
        <div key={key} className="mb-8">
          <div className="grid gap-4 md:grid-cols-4">
            {/* Pie Chart */}
            <PieChartComponent chartData={chartData[key]} title={`Tỷ lệ ${key.toUpperCase()}`} />

            {/* Bar Chart */}
            <BarChartComponent
              chartData={chartData[key]}
              title={`Tỷ lệ ${key.toUpperCase()}`}
              keyTitle={key}
              isExpanded={expandedStates[key] || false}
              toggleExpanded={() => toggleExpanded(key)}
            />
          </div>

          {/* Data Table - Hiển thị khi mở rộng */}
          {expandedStates[key] && (
            <div className="md:col-span-4 w-full mt-4">
              <DataTableBarComponent
                columns={chartData[key]?.barTable || []}
                data={Object.values(chartData[key]?.barDataTable || {})}
              />
            </div>
          )}
        </div>
      ))}
    </div>
  );
};

export default ChartDashboard;