import { createNavigationContainerRef } from "@react-navigation/native";
import { useEffect, useState } from "react";

export const navigationRef = createNavigationContainerRef<any>();

export function useCurrentRouteName(): string | undefined {
  const [routeName, setRouteName] = useState<string | undefined>(() => {
    return navigationRef.isReady() ? navigationRef.getCurrentRoute()?.name : undefined;
  });

  useEffect(() => {
    const updateRoute = () => {
      if (navigationRef.isReady()) {
        const route = navigationRef.getCurrentRoute();
        setRouteName(route?.name);
      }
    };

    updateRoute();
    const unsubscribe = navigationRef.addListener("state", updateRoute);
    return () => {
      if (unsubscribe) unsubscribe();
    };
  }, []);

  return routeName;
}

export function navigate(name: string, params?: any) {
  if (navigationRef.isReady()) {
    navigationRef.navigate(name, params);
  }
}

/**
 * Handle notification screen target resolution
 */
export function navigateToNotificationTarget(screen?: string) {
  if (!screen) return;

  const target = screen.trim();

  switch (target) {
    case "DayStart":
      navigate("DayStart");
      break;
    case "DayEnd":
      navigate("DayEnd");
      break;
    case "Visits":
    case "Customer":
    case "DealerList":
      navigate("DealerList");
      break;
    case "Orders":
    case "Order":
    case "OrderList":
      navigate("OrderList");
      break;
    case "Expenses":
    case "Expense":
    case "ExpenseList":
      navigate("ExpenseList");
      break;
    case "Leaves":
    case "Leave":
    case "MyLeaves":
      navigate("MyLeaves");
      break;
    case "LeaveApplication":
      navigate("LeaveApplication");
      break;
    case "Attendance":
    case "AttendanceCalendar":
      navigate("AttendanceCalendar");
      break;
    case "Products":
    case "ProductList":
      navigate("ProductList");
      break;
    case "Notifications":
      navigate("Notifications");
      break;
    case "Home":
    case "Dashboard":
    default:
      navigate("Dashboard");
      break;
  }
}
