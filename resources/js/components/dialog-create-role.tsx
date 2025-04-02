import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";


import { Deferred, Head, Link, router } from "@inertiajs/react";
import React, { FormEvent, useEffect, useMemo, useState } from "react";


// import { Area, Permission } from "@/types";
import { ColumnDef } from "@tanstack/react-table";
import ColumnHeader from "@/components/ui/column-header";
import DataTable from "@/components/data-table";
import ConFirmAlert from "@/components/confirm-alert";
import toast, { Toaster } from "react-hot-toast";
import AddAreaSheet from "@/components/add-area-sheet";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Edit, Loader2, MoreHorizontal, Trash2 } from "lucide-react";
import EditAreaSheet from "@/components/edit-area-sheet";

import AddPermissionSheet from "@/components/add-permission-sheet";
import InputLabel from "@/components/ui/InputLabel";
import TextInput from "@/components/ui/TextInput";
import InputError from "@/components/ui/InputError";
import { Separator } from "@/components/ui/separator";

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Checkbox } from "@/components/ui/checkbox";
import { useForm } from "@inertiajs/react";
import { ScrollArea } from "./ui/scroll-area";
import { DialogProps } from "@radix-ui/react-dialog";
type FormType = {
  name: string;
  permission: string[];
};

type Props = DialogProps & {
  permissions: any[];
};

export function DialogCreateRole({
  permissions,
  onOpenChange,
  ...props
}: Props) {
  console.log(permissions);
  const count = Object.keys(permissions).length;
  const { data, setData, post, errors, reset, processing } = useForm<FormType>({
    name: "",
    permission: [],
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    post(route("roles.store"), {
      onSuccess: () => {
        toast.success("Area has been saved successfully");
        reset();
        onOpenChange?.(false);
      },
    });
  };

  const capitalizeWords = (str: string) => {
    return str.replace(/\b\w/g, (char) => char.toUpperCase());
  };

  return (
    <Dialog onOpenChange={onOpenChange} {...props}>
      {/* <DialogTrigger asChild>
        <Button variant="default">Create New</Button>
      </DialogTrigger> */}
      <DialogContent className="sm:max-w-[1200px] sm:max-h-[1400px]">
        <DialogHeader >
          <DialogTitle className="text-center">Create Role</DialogTitle>
          <DialogDescription className="text-center">
            Make changes to your profile here. Click save when you're done.
          </DialogDescription>
        </DialogHeader>
        <form className="mt-6 space-y-6" onSubmit={handleSubmit}>
          <div>
            <InputLabel htmlFor="name" value="Name" />

            <TextInput
              id="name"
              className="mt-1 block w-full  py-2 px-6"
              value={data.name}
              onChange={(e) => setData("name", e.target.value)}
              required
              isFocused
              autoComplete="name"
            />

            <InputError className="mt-2" message={errors.name} />
          </div>

          <Tabs defaultValue="Dashboard & Reports" className="w-full">
            {/* <TabsList className={`grid w-full grid-cols-${count}`}> */}
            <TabsList className={`grid w-full grid-cols-${count}`}>
              {Object.keys(permissions).map((key: string) => (
                <TabsTrigger key={key} value={key}>
                  {key}
                </TabsTrigger>
              ))}
            </TabsList>
            {Object.entries(permissions).map((key: any) => (
              <TabsContent key={key[0]} value={key[0]}>
                <Card>
                  <ScrollArea className="w-full h-96">
                    <CardContent className="space-y-2 p-6">
                      <div className="flex flex-1 flex-col gap-4">
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-4 ">
                          {Object.entries(key[1]).map((key1: any) => (
                            <React.Fragment key={key1[0]}>
                              <div className="flex col-span-12 md:col-span-2 rounded-xl font-semibold uppercase">
                                {key1[0]}
                              </div>
                              <div className="col-span-12 md:col-span-10 rounded-xl ">
                                <div className="grid  md:grid-cols-12 gap-4 ">
                                  {key1[1].map((key2: any, index: number) => {
                                    const checkboxId = `checkbox-${key[0]}-${key1[0]}-${index}`;
                                    return (
                                      <div
                                        key={checkboxId}
                                        className="flex items-center space-x-2 col-span-12 md:col-span-3"
                                      >
                                        <Checkbox
                                          id={checkboxId}
                                          checked={data.permission.includes(
                                            key2.name
                                          )}
                                          onCheckedChange={(checked) => {
                                            setData(
                                              "permission",
                                              checked
                                                ? [
                                                  ...data.permission,
                                                  key2.name,
                                                ]
                                                : data.permission.filter(
                                                  (p) => p !== key2.name
                                                )
                                            );
                                          }}
                                        />
                                        <label
                                          htmlFor={checkboxId}
                                          className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                          {/* {key2.name} */}
                                          {capitalizeWords(key2.name)}
                                        </label>
                                      </div>
                                    );
                                  })}
                                </div>
                              </div>
                              <Separator className=" col-span-12 " />
                            </React.Fragment>
                          ))}
                        </div>
                      </div>
                    </CardContent>
                  </ScrollArea>
                </Card>
              </TabsContent>
            ))}
          </Tabs>

          <div className="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
            <Button disabled={processing} onSubmit={handleSubmit}>
              {processing && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
              {!processing ? "Save" : "Saving ..."}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
