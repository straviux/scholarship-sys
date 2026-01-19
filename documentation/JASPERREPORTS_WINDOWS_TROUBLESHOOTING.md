# JasperReports Setup Troubleshooting

## Issue: "JasperStarter: This application requires a Java Runtime Environment 1.8.0"

### Root Cause
JasperStarter 3.x ships with both .exe and shell script versions. The .exe version is often 32-bit and may have issues finding Java on Windows systems with 64-bit Java installations.

### Solutions

#### Solution 1: Use Direct Java JAR Execution (RECOMMENDED FOR WINDOWS)
Instead of using the jasperstarter executable, use JasperReports library directly via Java.

1. Download JasperReports standalone:
   - Download from: https://sourceforge.net/projects/jasperreports/files/
   - Extract to a known location

2. Or use the bundled JAR approach by installing jasperreports-functions JAR

#### Solution 2: Use 32-bit Java
If you need to use the jasperstarter.exe:
- Install 32-bit Java (OpenJDK 32-bit version)
- Set `JAVA_HOME` to the 32-bit Java installation
- Ensure 32-bit Java bin is in PATH

#### Solution 3: Use WSL (Windows Subsystem for Linux)
- Install WSL2
- Install Java and JasperStarter in WSL
- Run jasperstarter commands through WSL

#### Solution 4: Use Docker
- Use a Docker container with JasperStarter pre-configured
- Execute reports via Docker

### Current Setup Status

```
✅ Java 17.0.10 (64-bit) - Installed at C:\Program Files\Eclipse Adoptium\jdk-17.0.10.7-hotspot
✅ JasperStarter 3.x - Installed at C:\Program Files (x86)\JasperStarter
⚠️  JasperStarter.exe - 32-bit executable, incompatible with 64-bit Java
✅ Config & Directories - All configured properly
```

## Recommended Implementation Path

Given the Windows environment and 64-bit Java, we recommend implementing **Solution 1: Direct Java JAR Execution**.

This involves:
1. Downloading jasperreports-*.jar files
2. Using Java directly to compile and execute reports
3. Updating the JasperReportService to use Java -jar approach instead of jasperstarter

This will be more reliable and avoid the 32-bit/64-bit compatibility issues.

## Next Steps

Would you like me to:
1. **Implement direct Java JAR approach** - Modify services to use `java -jar` for report generation
2. **Install 32-bit Java** - Set up 32-bit OpenJDK for jasperstarter compatibility
3. **Switch to Docker** - Use Docker container for JasperReports
4. **Use alternative reporting tool** - Consider alternatives like DomPDF or TCPDF for PHP

Please confirm your preference, and I'll implement the solution.
