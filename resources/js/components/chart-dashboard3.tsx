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
import { FormTypeDashboard } from "@/types";

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
      {/* <path d={`M${sx},${sy}L${mx},${my}L${ex},${ey}`} stroke={fill} fill="none" />
      <circle cx={ex} cy={ey} r={3} fill={fill} stroke="none" />
      <text x={ex + (cos >= 0 ? 6 : -6)} y={ey} textAnchor={textAnchor} fill={fill} fontSize={10}>
        {`${payload.name}: ${value}`}
      </text>
       <text x={ex + (cos >= 0 ? 4 : -4)} y={ey} dy={16} textAnchor={textAnchor} fill="#ffffff" fontSize={10}>
        {`(${(percent * 100).toFixed(2)}%)`}
      </text> */}
      {value > 0 && (
        <>
          <path d={`M${sx},${sy}L${mx},${my}L${ex},${ey}`} stroke={fill} fill="none" />
          <circle cx={ex} cy={ey} r={3} fill={fill} stroke="none" />
          <text x={ex + (cos >= 0 ? 6 : -6)} y={ey} textAnchor={textAnchor} fill={fill} fontSize={10}>
          {`${payload.name}: ${value}`}
          </text>
           <text x={ex + (cos >= 0 ? 4 : -4)} y={ey} dy={16} textAnchor={textAnchor} fill="#ffffff" fontSize={10}>
        {`(${(percent * 100).toFixed(2)}%)`}
      </text>
        </>
      )}
     
    </g>
  );
};

const renderCustomTooltip = ({ active, payload }: any) => {
  if (active && payload?.length) {
    return (
      <div className="bg-white shadow-lg rounded-md p-2 border border-gray-200 text-sm">
        <p className="text-center  font-semibold text-gray-700 pb-2">{payload[0].payload.ttkv}</p>
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

// Memoize Pie Chart Component
const PieChartComponent = React.memo(({ chartData, title }: { chartData: any; title: string }) => {
  return (
    <Card >
      <CardHeader className="items-center pb-0 pt-4 w-full">
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="p-0 flex-1" style={{ height: 325 }}>
        <ResponsiveContainer width="100%" height="100%" >
          <PieChart>
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
                <Cell
                  key={`cell-${index}`}
                  fill={COLORS[index]}
                />
              ))}
            </Pie>
            <Legend
              layout="horizontal"
              align="center"
              verticalAlign="top"
              iconSize={10}
              wrapperStyle={{ fontSize: "12px", fontWeight: "bold", marginTop: "-10px" }}
            />

          </PieChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  );
}, (prevProps, nextProps) => {
  return prevProps.chartData === nextProps.chartData;
});

// Memoize Bar Chart Component
const BarChartComponent = React.memo(
  ({
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
    // console.log(chartData,title,keyTitle);
    return (
      <Card className="md:col-span-3">
        <CardHeader className="items-center pb-0 pt-4">
          <CardTitle className="label">{title}</CardTitle>
        </CardHeader>
        <div className="flex flex-col">
          <CardContent className="p-0" style={{ height: 325 }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={chartData?.barDataTable || []}
                // data={filteredBarDataTable}
                margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
              >
                <defs>
                  {Object.entries(chartData.allKeys).map((key: any, index: number) => (
                    <linearGradient id={`color-${index}`} key={index} x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor={COLORS[index]} stopOpacity={1} />
                      <stop offset="100%" stopColor={COLORS[index]} stopOpacity={0.4} />
                    </linearGradient>
                  ))}
                </defs>
                 {/* <defs>
                {chartData?.allKeys && Object.entries(chartData.allKeys).map((key: any, index: number) => (
                  <linearGradient id={`color-${index}`} key={index} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={COLORS[index]} stopOpacity={1} />
                    <stop offset="100%" stopColor={COLORS[index]} stopOpacity={0.4} />
                  </linearGradient>
                ))}</defs> */}
                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.5} vertical={false} />
                <XAxis dataKey="ttkv" />
                <YAxis yAxisId="left" label={{ value: "Số lượng", position: 'top', dy: -20, }} />
                {['wott','pakh'].includes(keyTitle) && (
                <YAxis yAxisId="right" orientation="right" label={{ value: "Giá trị (VNĐ)", position: 'top', dy: -20, }} />
                )}


                <Tooltip content={renderCustomTooltip} />

                <Legend
                  layout="horizontal"
                  align="center"
                  verticalAlign="top"
                  iconSize={10}
                  wrapperStyle={{ fontSize: "12px", fontWeight: "bold", marginTop: "-10px" }}
                />
                <ReferenceLine y={10} yAxisId="left" stroke="red" />
                {Object.entries(chartData.allKeys).map((key: any, index: number) => (
                  <Bar
                    key={key[0]}
                    dataKey={key[0]}
                    // fill={COLORS[index]}
                    fill={`url(#color-${index})`}
                    radius={[8, 8, 0, 0]}
                    barSize={30}
                    yAxisId={key[1]}
                    stroke="#fff"
                    strokeWidth={2}
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
  },
  (prevProps, nextProps) => {
    return (
      prevProps.chartData === nextProps.chartData &&
      prevProps.isExpanded === nextProps.isExpanded
    );
  }
);

// Memoize DataTableBar
const MemoizedDataTableBar = React.memo(
  DataTableBar,
  (prevProps, nextProps) => {
    return prevProps.columns === nextProps.columns && prevProps.data === nextProps.data;
  }
);

interface ChartDashboard3Props {
  data: any;
  filters?: FormTypeDashboard; // Nhận dữ liệu bộ lọc từ FilterSheet
}

const ChartDashboard3 = ({ data, filters }: ChartDashboard3Props) => {
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

  // console.log('chartData', chartData);
  return (
    <div className="flex flex-col gap-4">
      {Object.keys(chartData).map((key) => (
        <div key={key} className="mb-8">
          <div className="grid gap-4 md:grid-cols-4 shadow-2xl rounded"  >
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

          {/* Data Table */}
          {expandedStates[key] && (
            <div className="md:col-span-4 w-full mt-4">
              <MemoizedDataTableBar
                columns={chartData[key]?.barTable || []}
                data={Object.values(chartData[key]?.barDataTable || {})}
                name={key}
                filters={filters}
              />
            </div>
          )}
        </div>
      ))}
    </div>
  );
};

export default ChartDashboard3;