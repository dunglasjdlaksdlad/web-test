import { User } from "@/types";
import { Permission } from "./index";
export function can(user: any, permission: string): boolean {
  if (user.roles.includes("Super Admin")) {
    return true;
  }
  return user.permissions.includes(permission);
}

export function hasRole(user: any, role: string): boolean {
  return user.roles.includes(role);
}
