import React from "react";
import { parseDate } from "chrono-node";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { ActiveModifiers, DateRange } from "react-day-picker";
import { Calendar } from "@/components/ui/calendar";
import { Input } from "@/components/ui/input";
import { Button, buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { Calendar as CalendarIcon } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";

export const parseDateTime = (str: Date | string): Date | null => {
  if (str instanceof Date) return str;
  return parseDate(str);
};

export const getDateTimeLocal = (timestamp?: Date): string => {
  const d = timestamp ? new Date(timestamp) : new Date();
  if (d.toString() === "Invalid Date") return "";
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000)
    .toISOString()
    .split(":")
    .slice(0, 2)
    .join(":");
};

const getValidBaseDate = (
  disabled?: boolean | ((date: Date) => boolean)
): Date => {
  if (typeof disabled !== "function") return new Date();
  let potential = new Date();
  const MAX_DAYS = 365;
  for (let i = 0; i < MAX_DAYS; i++) {
    if (!disabled(potential)) {
      return potential;
    }
    potential = new Date(potential.getTime());
    potential.setDate(potential.getDate() + 1);
  }
  return new Date();
};

export const formatDateTime = (datetime: Date | string): string => {
  const date = typeof datetime === "string" ? new Date(datetime) : datetime;
  if (isNaN(date.getTime())) return "";
  return date.toLocaleTimeString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "numeric",
    hour12: true,
  });
};

// const formatDateTimeRange = (range: DateRange): string => {
//   if (!range.from) return "";
//   const fromStr = formatDateTime(range.from);
//   if (!range.to) return fromStr;
//   const toStr = formatDateTime(range.to);
//   return `${fromStr} - ${toStr}`;
// };

const formatDateTimeRange = (range?: DateRange): string => {
  if (!range || !range.from) return "";
  const fromStr = formatDateTime(range.from);
  if (!range.to) return fromStr;
  const toStr = formatDateTime(range.to);
  return `${fromStr} - ${toStr}`;
};

const inputBase =
  "bg-transparent focus:outline-none focus:ring-0 focus-within:outline-none focus-within:ring-0 sm:text-sm disabled:cursor-not-allowed disabled:opacity-50";

const DEFAULT_SIZE = 96;

type SmartDatetimeInputValue = Date | DateRange;

interface SmartDatetimeInputProps {
  value?: SmartDatetimeInputValue;
  onValueChange: (value: SmartDatetimeInputValue) => void;
  disabled?: boolean | ((date: Date) => boolean);
  mode?: "single" | "range";
}

interface SmartDatetimeInputContextProps extends SmartDatetimeInputProps {
  fromTime: string;
  toTime: string;
  onFromTimeChange: (time: string) => void;
  onToTimeChange: (time: string) => void;
}

const SmartDatetimeInputContext =
  React.createContext<SmartDatetimeInputContextProps | null>(null);

const useSmartDateInput = () => {
  const context = React.useContext(SmartDatetimeInputContext);
  if (!context) {
    throw new Error(
      "useSmartDateInput must be used within SmartDateInputProvider"
    );
  }
  return context;
};

export const SmartDatetimeInput = React.forwardRef<
  HTMLInputElement,
  Omit<
    React.InputHTMLAttributes<HTMLInputElement>,
    "disabled" | "type" | "ref" | "value" | "defaultValue" | "onBlur"
  > &
  SmartDatetimeInputProps
>(({ className, value, onValueChange, placeholder, disabled, mode }, ref) => {
  const getInitialTime = () => {
    const now = new Date();
    const hour = now.getHours();
    const minutes = now.getMinutes();
    const PM_AM = hour >= 12 ? "PM" : "AM";
    const formattedHour = hour > 12 ? hour % 12 : hour === 0 ? 12 : hour;
    return `${formattedHour}:${minutes.toString().padStart(2, "0")} ${PM_AM}`;
  };

  const isRangeMode = mode === "range";
  // const initialFromTime = isRangeMode
  //   ? (value as DateRange)?.from
  //     ? formatDateTime((value as DateRange).from).split(", ")[1]
  //     : getInitialTime()
  //   : value instanceof Date
  //     ? formatDateTime(value).split(", ")[1]
  //     : getInitialTime();

  // const initialToTime = isRangeMode
  //   ? (value as DateRange)?.to
  //     ? formatDateTime((value as DateRange).to).split(", ")[1]
  //     : getInitialTime()
  //   : getInitialTime();
  const initialFromTime = isRangeMode
    ? (value as DateRange)?.from
      ? formatDateTime((value as DateRange).from!).split(", ")[1] // Sử dụng ! để đảm bảo from không undefined
      : getInitialTime()
    : value instanceof Date
      ? formatDateTime(value).split(", ")[1]
      : getInitialTime();

  const initialToTime = isRangeMode
    ? (value as DateRange)?.to
      ? formatDateTime((value as DateRange).to!).split(", ")[1] // Sử dụng ! để đảm bảo to không undefined
      : getInitialTime()
    : getInitialTime();

  const [fromTime, setFromTime] = React.useState<string>(initialFromTime);
  const [toTime, setToTime] = React.useState<string>(initialToTime);

  const onFromTimeChange = React.useCallback((time: string) => {
    if (time && /^(\d{1,2}:\d{2}\s[AP]M)$/.test(time)) {
      setFromTime(time);
    }
  }, []);

  const onToTimeChange = React.useCallback((time: string) => {
    if (time && /^(\d{1,2}:\d{2}\s[AP]M)$/.test(time)) {
      setToTime(time);
    }
  }, []);

  return (
    <SmartDatetimeInputContext.Provider
      value={{
        value,
        onValueChange,
        fromTime,
        toTime,
        onFromTimeChange,
        onToTimeChange,
        disabled,
        mode,
      }}
    >
      <div className="flex items-center justify-center">
        <div
          className={cn(
            "flex gap-1 w-full p-1 items-center justify-between rounded-md border transition-all",
            "focus-within:outline-0 focus:outline-0 focus:ring-0",
            "placeholder:text-muted-foreground focus-visible:outline-0",
            className
          )}
        >
          <DateTimeLocalInput disabled={disabled} />
          <NaturalLanguageInput
            placeholder={placeholder}
            disabled={typeof disabled === "boolean" ? disabled : false}
            ref={ref}
          />
        </div>
      </div>
    </SmartDatetimeInputContext.Provider>
  );
});

SmartDatetimeInput.displayName = "DatetimeInput";

const TimePicker = ({ isToTime = false }) => {
  const {
    value,
    onValueChange,
    fromTime,
    toTime,
    onFromTimeChange,
    onToTimeChange,
    disabled,
    mode,
  } = useSmartDateInput();
  const [activeIndex, setActiveIndex] = React.useState(-1);
  const timestamp = 15;

  const isRangeMode = mode === "range";
  const currentTimeStr = isToTime ? toTime : fromTime;
  const onTimeChange = isToTime ? onToTimeChange : onFromTimeChange;

  const formatSelectedTime = React.useCallback(
    (time: string, hour: number, partStamp: number) => {
      onTimeChange(time);

      const base = isRangeMode
        ? isToTime
          ? (value as DateRange)?.to || (value as DateRange)?.from || getValidBaseDate(disabled)
          : (value as DateRange)?.from || getValidBaseDate(disabled)
        : value instanceof Date
          ? value
          : getValidBaseDate(disabled);
      const newVal = new Date(base);

      if (isNaN(newVal.getTime())) return;

      newVal.setHours(
        hour,
        partStamp === 0 ? 0 : timestamp * partStamp,
        0,
        0
      );

      if (!isNaN(newVal.getTime())) {
        if (isRangeMode) {
          const newRange = { ...(value as DateRange) };
          if (isToTime) {
            newRange.to = newVal;
            if (newRange.from && newRange.to < newRange.from) {
              newRange.to = new Date(newRange.from);
              newRange.to.setHours(
                hour,
                partStamp === 0 ? 0 : timestamp * partStamp,
                0,
                0
              );
            }
          } else {
            newRange.from = newVal;
            if (newRange.to && newRange.to < newRange.from) {
              newRange.to = new Date(newRange.from);
              newRange.to.setHours(
                hour,
                partStamp === 0 ? 0 : timestamp * partStamp,
                0,
                0
              );
            }
          }
          onValueChange(newRange);
        } else {
          onValueChange(newVal);
        }
      }
    },
    [value, onValueChange, onTimeChange, disabled, isToTime, isRangeMode]
  );

  const handleKeydown = React.useCallback(
    (e: React.KeyboardEvent<HTMLDivElement>) => {
      e.stopPropagation();

      if (!document) return;

      const moveNext = () => {
        const nextIndex =
          activeIndex + 1 > DEFAULT_SIZE - 1 ? 0 : activeIndex + 1;
        const currentElm = document.getElementById(
          `time-${isToTime ? "to-" : "from-"}${nextIndex}`
        );
        currentElm?.focus();
        setActiveIndex(nextIndex);
      };

      const movePrev = () => {
        const prevIndex =
          activeIndex - 1 < 0 ? DEFAULT_SIZE - 1 : activeIndex - 1;
        const currentElm = document.getElementById(
          `time-${isToTime ? "to-" : "from-"}${prevIndex}`
        );
        currentElm?.focus();
        setActiveIndex(prevIndex);
      };

      const setElement = () => {
        const currentElm = document.getElementById(
          `time-${isToTime ? "to-" : "from-"}${activeIndex}`
        );
        if (!currentElm) return;

        currentElm.focus();

        const timeValue = currentElm.textContent ?? "";
        if (!timeValue || !/^(\d{1,2}:\d{2}\s[AP]M)$/.test(timeValue)) return;

        const PM_AM = timeValue.split(" ")[1];
        const PM_AM_hour = parseInt(timeValue.split(" ")[0].split(":")[0]);
        const hour =
          PM_AM === "AM"
            ? PM_AM_hour === 12
              ? 0
              : PM_AM_hour
            : PM_AM_hour === 12
              ? 12
              : PM_AM_hour + 12;

        const part = Math.floor(
          parseInt(timeValue.split(" ")[0].split(":")[1]) / 15
        );

        formatSelectedTime(timeValue, hour, part);
      };

      const reset = () => {
        const currentElm = document.getElementById(
          `time-${isToTime ? "to-" : "from-"}${activeIndex}`
        );
        currentElm?.blur();
        setActiveIndex(-1);
      };

      switch (e.key) {
        case "ArrowUp":
          movePrev();
          break;
        case "ArrowDown":
          moveNext();
          break;
        case "Escape":
          reset();
          break;
        case "Enter":
          setElement();
          break;
      }
    },
    [activeIndex, formatSelectedTime, isToTime]
  );

  const handleClick = React.useCallback(
    (hour: number, part: number, PM_AM: string, currentIndex: number) => {
      const time = `${hour}:${part === 0 ? "00" : timestamp * part} ${PM_AM}`;
      formatSelectedTime(time, hour, part);
      setActiveIndex(currentIndex);
    },
    [formatSelectedTime]
  );

  const currentTime = React.useMemo(() => {
    if (!currentTimeStr || !/^(\d{1,2}:\d{2}\s[AP]M)$/.test(currentTimeStr)) {
      return { hours: 0, minutes: 0 };
    }
    const timeVal = currentTimeStr.split(" ")[0];
    const hours = parseInt(timeVal.split(":")[0]) || 0;
    const minutes = parseInt(timeVal.split(":")[1]) || 0;
    return { hours, minutes };
  }, [currentTimeStr]);

  React.useEffect(() => {
    const getCurrentElementTime = () => {
      if (!currentTimeStr || !/^(\d{1,2}:\d{2}\s[AP]M)$/.test(currentTimeStr))
        return;

      const timeVal = currentTimeStr.split(" ")[0];
      const hours = parseInt(timeVal.split(":")[0]) || 0;
      const minutes = parseInt(timeVal.split(":")[1]) || 0;
      const PM_AM = currentTimeStr.split(" ")[1];

      const formatIndex =
        PM_AM === "AM" ? hours : hours === 12 ? hours : hours + 12;

      for (let j = 0; j <= 3; j++) {
        const diff = Math.abs(j * timestamp - minutes);
        const selected =
          PM_AM === (formatIndex >= 12 ? "PM" : "AM") &&
          (minutes <= 53 ? diff < Math.ceil(timestamp / 2) : diff < timestamp);

        if (selected) {
          const trueIndex =
            activeIndex === -1 ? formatIndex * 4 + j : activeIndex;

          setActiveIndex(trueIndex);

          const currentElm = document.getElementById(
            `time-${isToTime ? "to-" : "from-"}${trueIndex}`
          );
          currentElm?.scrollIntoView({
            block: "center",
            behavior: "smooth",
          });
        }
      }
    };

    getCurrentElementTime();
  }, [currentTimeStr, activeIndex, isToTime]);

  const height = React.useMemo(() => {
    if (!document) return;
    const calendarElm = document.getElementById("calendar");
    if (!calendarElm) return;
    return calendarElm.style.height;
  }, []);

  return (
    <div className="space-y-2 pr-3 py-3 relative">
      <h3 className="text-sm font-medium">{isToTime ? "To Time" : "From Time"}</h3>
      <ScrollArea
        onKeyDown={handleKeydown}
        className="h-[90%] w-full focus-visible:outline-0 focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:border-0 py-0.5"
        style={{ height }}
      >
        <ul
          className={cn(
            "flex items-center flex-col gap-1 h-full max-h-56 w-28 px-1 py-0.5"
          )}
        >
          {Array.from({ length: 24 }).map((_, i) => {
            const PM_AM = i >= 12 ? "PM" : "AM";
            const formatIndex = i > 12 ? i % 12 : i === 0 || i === 12 ? 12 : i;
            return Array.from({ length: 4 }).map((_, part) => {
              const baseDate = isRangeMode
                ? (isToTime
                  ? (value as DateRange)?.to || (value as DateRange)?.from
                  : (value as DateRange)?.from) || getValidBaseDate(disabled)
                : value instanceof Date
                  ? value
                  : getValidBaseDate(disabled);
              const candidateDate = new Date(
                baseDate.getFullYear(),
                baseDate.getMonth(),
                baseDate.getDate(),
                i,
                part === 0 ? 0 : timestamp * part,
                0,
                0
              );

              let candidateDisabled =
                typeof disabled === "function" ? disabled(candidateDate) : false;

              if (candidateDisabled) return null;

              const diff = Math.abs(part * timestamp - currentTime.minutes);
              const trueIndex = i * 4 + part;
              const isSelected =
                (currentTime.hours === i ||
                  currentTime.hours === formatIndex) &&
                currentTimeStr.split(" ")[1] === PM_AM &&
                (currentTime.minutes <= 53
                  ? diff < Math.ceil(timestamp / 2)
                  : diff < timestamp);

              const isSuggested =
                !isRangeMode && !value
                  ? isSelected
                  : isToTime
                    ? !(value as DateRange)?.to && isSelected
                    : !(value as DateRange)?.from && isSelected;
              const currentValue = `${formatIndex}:${part === 0 ? "00" : timestamp * part
                } ${PM_AM}`;

              return (
                <li
                  tabIndex={isSelected ? 0 : -1}
                  id={`time-${isToTime ? "to-" : "from-"}${trueIndex}`}
                  key={`time-${isToTime ? "to-" : "from-"}${trueIndex}`}
                  aria-label="currentTime"
                  className={cn(
                    buttonVariants({
                      variant: isSuggested
                        ? "secondary"
                        : isSelected
                          ? "default"
                          : "outline",
                    }),
                    "h-8 px-3 w-full text-sm focus-visible:outline-0 outline-0 focus-visible:border-0 cursor-default ring-0"
                  )}
                  onClick={() => handleClick(i, part, PM_AM, trueIndex)}
                  onFocus={() => isSuggested && setActiveIndex(trueIndex)}
                >
                  {currentValue}
                </li>
              );
            });
          })}
        </ul>
      </ScrollArea>
    </div>
  );
};

const NaturalLanguageInput = React.forwardRef<
  HTMLInputElement,
  { placeholder?: string; disabled?: boolean }
>(({ placeholder, ...props }, ref) => {
  const {
    value,
    onValueChange,
    fromTime,
    onFromTimeChange,
    toTime,
    onToTimeChange,
    disabled,
    mode,
  } = useSmartDateInput();
  const isRangeMode = mode === "range";
  const _placeholder =
    placeholder ??
    (isRangeMode
      ? 'e.g. "today in 30 min - tomorrow at 3pm" or "from tomorrow at 5pm to next Friday at 8pm"'
      : 'e.g. "tomorrow at 5pm"');
  const [inputValue, setInputValue] = React.useState<string>("");

  React.useEffect(() => {
    if (isRangeMode) {
      const range = value as DateRange;
      setInputValue(range && range.from ? formatDateTimeRange(range) : "");
    } else {
      setInputValue(value instanceof Date ? formatDateTime(value) : "");
    }
  }, [value, isRangeMode]);

  const handleParse = React.useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const input = e.currentTarget.value;
      if (isRangeMode) {
        ///////////////////////// from ... to ... /// OR /// ... - ...
        const rangeMatch = input.match(/from (.+?) to (.+)/i) || input.match(/(.+?)\s*-\s*(.+)/i);
        ///////////////////////// from ... to ... /// OR /// ... - ...
        if (rangeMatch) {
          const fromStr = rangeMatch[1].trim();
          const toStr = rangeMatch[2].trim();
          const fromDate = parseDateTime(fromStr);
          const toDate = parseDateTime(toStr);
          if (
            fromDate &&
            !isNaN(fromDate.getTime()) &&
            toDate &&
            !isNaN(toDate.getTime())
          ) {
            if (
              disabled &&
              typeof disabled !== "boolean" &&
              (disabled(fromDate) || disabled(toDate))
            ) {
              return;
            }
            const newRange: DateRange = { from: fromDate, to: toDate };
            onValueChange(newRange);
            setInputValue(formatDateTimeRange(newRange));

            const fromHour = fromDate.getHours();
            const fromMinutes = fromDate.getMinutes();
            const fromPM_AM = fromHour >= 12 ? "PM" : "AM";
            const fromFormattedHour =
              fromHour > 12
                ? fromHour % 12
                : fromHour === 0 || fromHour === 12
                  ? 12
                  : fromHour;
            onFromTimeChange(
              `${fromFormattedHour}:${fromMinutes.toString().padStart(2, "0")} ${fromPM_AM}`
            );

            const toHour = toDate.getHours();
            const toMinutes = toDate.getMinutes();
            const toPM_AM = toHour >= 12 ? "PM" : "AM";
            const toFormattedHour =
              toHour > 12
                ? toHour % 12
                : toHour === 0 || toHour === 12
                  ? 12
                  : toHour;
            onToTimeChange(
              `${toFormattedHour}:${toMinutes.toString().padStart(2, "0")} ${toPM_AM}`
            );
          }
        }
      } else {
        const parsedDateTime = parseDateTime(input);
        if (parsedDateTime && !isNaN(parsedDateTime.getTime())) {
          if (
            disabled &&
            typeof disabled !== "boolean" &&
            disabled(parsedDateTime)
          ) {
            return;
          }
          const PM_AM = parsedDateTime.getHours() >= 12 ? "PM" : "AM";
          const hour =
            parsedDateTime.getHours() > 12
              ? parsedDateTime.getHours() % 12
              : parsedDateTime.getHours() === 0 || parsedDateTime.getHours() === 12
                ? 12
                : parsedDateTime.getHours();
          onValueChange(parsedDateTime);
          setInputValue(formatDateTime(parsedDateTime));
          onFromTimeChange(
            `${hour}:${parsedDateTime.getMinutes().toString().padStart(2, "0")} ${PM_AM}`
          );
        }
      }
    },
    [onValueChange, onFromTimeChange, onToTimeChange, disabled, isRangeMode]
  );

  const handleKeydown = React.useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === "Enter") {
        const input = e.currentTarget.value;
        if (isRangeMode) {
          ///////////////////////// from ... to ... /// OR /// ... - ...
          const rangeMatch = input.match(/from (.+?) to (.+)/i) || input.match(/(.+?)\s*-\s*(.+)/i);
          ///////////////////////// from ... to ... /// OR /// ... - ...
          if (rangeMatch) {
            const fromStr = rangeMatch[1].trim();
            const toStr = rangeMatch[2].trim();
            const fromDate = parseDateTime(fromStr);
            const toDate = parseDateTime(toStr);
            if (
              fromDate &&
              !isNaN(fromDate.getTime()) &&
              toDate &&
              !isNaN(toDate.getTime())
            ) {
              if (
                disabled &&
                typeof disabled !== "boolean" &&
                (disabled(fromDate) || disabled(toDate))
              ) {
                return;
              }
              const newRange: DateRange = { from: fromDate, to: toDate };
              onValueChange(newRange);
              setInputValue(formatDateTimeRange(newRange));

              const fromHour = fromDate.getHours();
              const fromMinutes = fromDate.getMinutes();
              const fromPM_AM = fromHour >= 12 ? "PM" : "AM";
              const fromFormattedHour =
                fromHour > 12
                  ? fromHour % 12
                  : fromHour === 0 || fromHour === 12
                    ? 12
                    : fromHour;
              onFromTimeChange(
                `${fromFormattedHour}:${fromMinutes.toString().padStart(2, "0")} ${fromPM_AM}`
              );

              const toHour = toDate.getHours();
              const toMinutes = toDate.getMinutes();
              const toPM_AM = toHour >= 12 ? "PM" : "AM";
              const toFormattedHour =
                toHour > 12
                  ? toHour % 12
                  : toHour === 0 || toHour === 12
                    ? 12
                    : toHour;
              onToTimeChange(
                `${toFormattedHour}:${toMinutes.toString().padStart(2, "0")} ${toPM_AM}`
              );
            }
          }
        } else {
          const parsedDateTime = parseDateTime(input);
          if (parsedDateTime && !isNaN(parsedDateTime.getTime())) {
            if (
              disabled &&
              typeof disabled !== "boolean" &&
              disabled(parsedDateTime)
            ) {
              return;
            }
            const PM_AM = parsedDateTime.getHours() >= 12 ? "PM" : "AM";
            const hour =
              parsedDateTime.getHours() > 12
                ? parsedDateTime.getHours() % 12
                : parsedDateTime.getHours() === 0 || parsedDateTime.getHours() === 12
                  ? 12
                  : parsedDateTime.getHours();
            onValueChange(parsedDateTime);
            setInputValue(formatDateTime(parsedDateTime));
            onFromTimeChange(
              `${hour}:${parsedDateTime.getMinutes().toString().padStart(2, "0")} ${PM_AM}`
            );
          }
        }
      }
    },
    [onValueChange, onFromTimeChange, onToTimeChange, disabled, isRangeMode]
  );

  return (
    <Input
      ref={ref}
      type="text"
      placeholder={_placeholder}
      value={inputValue}
      onChange={(e) => setInputValue(e.currentTarget.value)}
      onKeyDown={handleKeydown}
      onBlur={handleParse}
      className={cn("px-2 mr-0.5 flex-1 border-none h-8 rounded", inputBase)}
      {...props}
    />
  );
});

NaturalLanguageInput.displayName = "NaturalLanguageInput";

type DateTimeLocalInputProps = {
  disabled?: boolean | ((date: Date) => boolean);
} & React.ComponentProps<typeof Calendar>;

const DateTimeLocalInput = ({ className, disabled, ...props }: DateTimeLocalInputProps) => {
  const { value, onValueChange, fromTime, toTime, mode } = useSmartDateInput();
  const isRangeMode = mode === "range";

  const formatSelectedDate = React.useCallback(
    (
      selected: Date | DateRange | undefined,
      selectedDate: Date,
      m: ActiveModifiers,
      e: React.MouseEvent
    ) => {
      if (typeof disabled === "boolean" && disabled) return;
      if (typeof disabled === "function" && disabled(selectedDate)) return;

      if (isRangeMode) {
        const newRange = { ...(value as DateRange) };

        if (selected && "from" in selected) {
          newRange.from = selected.from;
          newRange.to = selected.to;
        }

        if (newRange.from && fromTime && /^(\d{1,2}:\d{2}\s[AP]M)$/.test(fromTime)) {
          const [timeVal, PM_AM] = fromTime.split(" ");
          const [hoursStr, minutesStr] = timeVal.split(":");
          let hours = parseInt(hoursStr);
          const minutes = parseInt(minutesStr);

          if (PM_AM === "PM" && hours !== 12) hours += 12;
          if (PM_AM === "AM" && hours === 12) hours = 0;

          newRange.from.setHours(hours, minutes, 0, 0);
        }

        if (newRange.to && toTime && /^(\d{1,2}:\d{2}\s[AP]M)$/.test(toTime)) {
          const [timeVal, PM_AM] = toTime.split(" ");
          const [hoursStr, minutesStr] = timeVal.split(":");
          let hours = parseInt(hoursStr);
          const minutes = parseInt(minutesStr);

          if (PM_AM === "PM" && hours !== 12) hours += 12;
          if (PM_AM === "AM" && hours === 12) hours = 0;

          newRange.to.setHours(hours, minutes, 0, 0);
        }

        if (newRange.from && newRange.to && newRange.to < newRange.from) {
          newRange.to = new Date(newRange.from);
          if (toTime && /^(\d{1,2}:\d{2}\s[AP]M)$/.test(toTime)) {
            const [timeVal, PM_AM] = toTime.split(" ");
            const [hoursStr, minutesStr] = timeVal.split(":");
            let hours = parseInt(hoursStr);
            const minutes = parseInt(minutesStr);

            if (PM_AM === "PM" && hours !== 12) hours += 12;
            if (PM_AM === "AM" && hours === 12) hours = 0;

            newRange.to.setHours(hours, minutes, 0, 0);
          }
        }

        if (
          (newRange.from && !isNaN(newRange.from.getTime())) ||
          (newRange.to && !isNaN(newRange.to.getTime()))
        ) {
          onValueChange(newRange);
        }
      } else {
        const parsedDateTime = new Date(selectedDate);
        if (fromTime && /^(\d{1,2}:\d{2}\s[AP]M)$/.test(fromTime)) {
          const [timeVal, PM_AM] = fromTime.split(" ");
          const [hoursStr, minutesStr] = timeVal.split(":");
          let hours = parseInt(hoursStr);
          const minutes = parseInt(minutesStr);

          if (PM_AM === "PM" && hours !== 12) hours += 12;
          if (PM_AM === "AM" && hours === 12) hours = 0;

          parsedDateTime.setHours(hours, minutes, 0, 0);
        }

        if (!isNaN(parsedDateTime.getTime())) {
          onValueChange(parsedDateTime);
        }
      }
    },
    [value, fromTime, toTime, onValueChange, disabled, isRangeMode]
  );

  const renderCalendar = () => {
    if (isRangeMode) {
      return (
        <Calendar
          disabled={disabled}
          {...props}
          id="calendar"
          className={cn("peer flex justify-end", inputBase, className)}
          mode="range"
          selected={value as DateRange}
          onSelect={formatSelectedDate}
          initialFocus
        />
      );
    }
    return (
      <Calendar
        disabled={disabled}
        {...props}
        id="calendar"
        className={cn("peer flex justify-end", inputBase, className)}
        mode="single"
        selected={value as Date}
        onSelect={formatSelectedDate}
        initialFocus
      />
    );
  };

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          disabled={typeof disabled === "boolean" ? disabled : false}
          variant={"outline"}
          size={"icon"}
          className={cn(
            "size-9 flex items-center justify-center font-normal",
            !value && "text-muted-foreground"
          )}
        >
          <CalendarIcon className="size-4" />
          <span className="sr-only">calendar</span>
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" sideOffset={8}>
        <div className="flex gap-1">
          {/* <Calendar
            disabled={disabled}
            {...props}
            id={"calendar"}
            className={cn("peer flex justify-end", inputBase, className)}
            mode={mode}
            selected={value}
            onSelect={formatSelectedDate}
            initialFocus
          /> */}
          {renderCalendar()}
          <div className="flex flex-row gap-1">
            <TimePicker isToTime={false} />
            {isRangeMode && <TimePicker isToTime={true} />}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
};

DateTimeLocalInput.displayName = "DateTimeLocalInput";